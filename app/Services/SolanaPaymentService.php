<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// On-chain payment verification for Solana Club. Fail-closed: any uncertainty
// (RPC error, missing tx, wrong recipient/amount/sender/memo) -> not verified.
class SolanaPaymentService
{
    private const LAMPORTS_PER_SOL = 1_000_000_000;

    /** Live SOL/USD rate from the oracle (cached briefly). Throws on failure. */
    public function getSolUsdRate(): float
    {
        return Cache::remember('solana_sol_usd', (int) config('solana.price_cache_secs', 60), function () {
            $resp = Http::timeout(8)->get(config('solana.price_url'));
            $rate = (float) data_get($resp->json(), 'solana.usd', 0);

            if (!$resp->ok() || $rate <= 0) {
                throw new \RuntimeException('Unable to fetch SOL/USD price');
            }

            return $rate;
        });
    }

    public function usdToLamports(float $usd, float $rate): int
    {
        return (int) round(($usd / $rate) * self::LAMPORTS_PER_SOL);
    }

    /**
     * Verify a finalized SOL transfer to the treasury.
     * All checks must pass; returns false on any failure.
     */
    public function verifyPayment(
        string $signature,
        string $treasuryWallet,
        int $expectedLamports,
        string $payerWallet,
        string $reference,
        int $toleranceBps = 50
    ): bool {
        try {
            $tx = $this->getTransaction($signature);

            if (!$tx) {
                Log::warning('club-pay: tx not found/finalized', ['sig' => $signature]);
                return false;
            }

            // Must have executed without error.
            if (data_get($tx, 'meta.err') !== null) {
                Log::warning('club-pay: tx has error', ['sig' => $signature]);
                return false;
            }

            $accountKeys = $this->accountPubkeys($tx);
            if (empty($accountKeys)) {
                return false;
            }

            // Sender binding: the fee payer (account index 0) must be the player's wallet.
            if (!hash_equals($payerWallet, (string) $accountKeys[0])) {
                Log::warning('club-pay: payer mismatch', ['sig' => $signature, 'payer' => $accountKeys[0]]);
                return false;
            }

            // Amount + recipient: treasury's net balance increase must cover the price.
            $treasuryIdx = array_search($treasuryWallet, $accountKeys, true);
            if ($treasuryIdx === false) {
                Log::warning('club-pay: treasury not in tx', ['sig' => $signature]);
                return false;
            }

            $pre  = (int) data_get($tx, "meta.preBalances.$treasuryIdx", 0);
            $post = (int) data_get($tx, "meta.postBalances.$treasuryIdx", 0);
            $received = $post - $pre;

            $minRequired = (int) floor($expectedLamports * (1 - $toleranceBps / 10000));
            if ($received < $minRequired) {
                Log::warning('club-pay: underpaid', ['sig' => $signature, 'received' => $received, 'required' => $minRequired]);
                return false;
            }

            // Reference binding: the one-time nonce must appear in the tx memo.
            if (!$this->isMemoMatch($tx, $reference)) {
                Log::warning('club-pay: memo/reference mismatch', ['sig' => $signature]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('club-pay: verify exception', ['sig' => $signature, 'error' => $e->getMessage()]);
            return false; // fail-closed
        }
    }

    /** Native SOL balance of a wallet (in SOL). Throws on RPC error. */
    public function getSolBalance(string $wallet): float
    {
        $resp = Http::timeout(12)->post(config('solana.rpc_url'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$wallet],
        ]);

        if (!$resp->ok()) {
            throw new \RuntimeException('RPC error: ' . $resp->status());
        }

        return ((int) data_get($resp->json(), 'result.value', 0)) / self::LAMPORTS_PER_SOL;
    }

    /**
     * Total $HOTEL (SPL) ui-balance a wallet holds for a given mint. Used by the access gate.
     * Throws on RPC error so the caller can fail-open (never lock players out on RPC trouble).
     */
    public function getTokenBalance(string $wallet, string $mint): float
    {
        $resp = Http::timeout(12)->post(config('solana.rpc_url'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTokenAccountsByOwner',
            'params' => [$wallet, ['mint' => $mint], ['encoding' => 'jsonParsed']],
        ]);

        if (!$resp->ok()) {
            throw new \RuntimeException('RPC error: ' . $resp->status());
        }

        $total = 0.0;
        foreach ((array) data_get($resp->json(), 'result.value', []) as $acc) {
            $total += (float) data_get($acc, 'account.data.parsed.info.tokenAmount.uiAmount', 0);
        }

        return $total;
    }

    /**
     * Verify a finalized $HOTEL (SPL) swap that paid the seller AND the treasury in one tx.
     * Checks: executed ok, fee-payer = buyer wallet, reference memo present, the seller's token
     * account (mint=$HOTEL) increased by >= sellerAmount, and treasury's by >= feeAmount. Fail-closed.
     */
    public function verifyHotelSwap(
        string $signature,
        string $mint,
        string $payerWallet,
        string $sellerWallet,
        string $treasuryWallet,
        float $sellerAmount,
        float $feeAmount,
        string $reference
    ): bool {
        try {
            $tx = $this->getTransaction($signature);
            if (!$tx || data_get($tx, 'meta.err') !== null) {
                return false;
            }

            $accountKeys = $this->accountPubkeys($tx);
            if (empty($accountKeys) || !hash_equals($payerWallet, (string) $accountKeys[0])) {
                return false;
            }

            if (!$this->isMemoMatch($tx, $reference)) {
                return false;
            }

            $sellerDelta = $this->tokenDelta($tx, $mint, $sellerWallet);
            $treasuryDelta = $this->tokenDelta($tx, $mint, $treasuryWallet);

            // 0.1% tolerance for rounding at token-decimal precision.
            return $sellerDelta >= ($sellerAmount * 0.999) && $treasuryDelta >= ($feeAmount * 0.999);
        } catch (\Throwable $e) {
            Log::error('hotel-swap: verify exception', ['sig' => $signature, 'error' => $e->getMessage()]);
            return false; // fail-closed
        }
    }

    /** Net ui-amount change of the (owner, mint) token balance across the tx (post - pre). */
    private function tokenDelta(array $tx, string $mint, string $owner): float
    {
        $pre = 0.0;
        $post = 0.0;

        foreach ((array) data_get($tx, 'meta.preTokenBalances', []) as $b) {
            if (($b['mint'] ?? '') === $mint && ($b['owner'] ?? '') === $owner) {
                $pre += (float) data_get($b, 'uiTokenAmount.uiAmount', 0);
            }
        }
        foreach ((array) data_get($tx, 'meta.postTokenBalances', []) as $b) {
            if (($b['mint'] ?? '') === $mint && ($b['owner'] ?? '') === $owner) {
                $post += (float) data_get($b, 'uiTokenAmount.uiAmount', 0);
            }
        }

        return $post - $pre;
    }

    private function getTransaction(string $signature): ?array
    {
        $resp = Http::timeout(15)->post(config('solana.rpc_url'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTransaction',
            'params' => [
                $signature,
                [
                    'encoding' => 'jsonParsed',
                    'commitment' => config('solana.min_confirmation', 'finalized'),
                    'maxSupportedTransactionVersion' => 0,
                ],
            ],
        ]);

        if (!$resp->ok()) {
            throw new \RuntimeException('RPC error: ' . $resp->status());
        }

        return data_get($resp->json(), 'result'); // null if not yet finalized / unknown
    }

    /** Flat list of account pubkeys (jsonParsed: array of {pubkey,...}). */
    private function accountPubkeys(array $tx): array
    {
        $keys = data_get($tx, 'transaction.message.accountKeys', []);

        return array_map(fn ($k) => is_array($k) ? ($k['pubkey'] ?? '') : (string) $k, $keys);
    }

    /** True if any (inner) instruction is a memo whose text contains the reference. */
    private function isMemoMatch(array $tx, string $reference): bool
    {
        $haystacks = [];

        foreach ((array) data_get($tx, 'transaction.message.instructions', []) as $ix) {
            if (($ix['program'] ?? '') === 'spl-memo' || str_contains((string) ($ix['programId'] ?? ''), 'Memo')) {
                $haystacks[] = (string) ($ix['parsed'] ?? '');
            }
        }
        foreach ((array) data_get($tx, 'meta.innerInstructions', []) as $inner) {
            foreach ((array) ($inner['instructions'] ?? []) as $ix) {
                if (($ix['program'] ?? '') === 'spl-memo' || str_contains((string) ($ix['programId'] ?? ''), 'Memo')) {
                    $haystacks[] = (string) ($ix['parsed'] ?? '');
                }
            }
        }
        // Fallback: program logs often echo the memo text.
        foreach ((array) data_get($tx, 'meta.logMessages', []) as $log) {
            $haystacks[] = (string) $log;
        }

        foreach ($haystacks as $h) {
            if ($h !== '' && str_contains($h, $reference)) {
                return true;
            }
        }

        return false;
    }
}
