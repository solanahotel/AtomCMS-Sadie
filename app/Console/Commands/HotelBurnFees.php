<?php

namespace App\Console\Commands;

use App\Services\HotelTokenService;
use App\Services\SolanaPaymentService;
use Illuminate\Console\Command;

// Batched treasury burn: reports the $HOTEL fees sitting in the burn wallet and (once the burn-key
// custody is decided) burns them. Schedule this (e.g. daily) when the token is live.
class HotelBurnFees extends Command
{
    protected $signature = 'hotel:burn-fees {--dry-run : Only report the balance, never burn}';
    protected $description = 'Burn accumulated $HOTEL fees held in the burn wallet (batched treasury burn)';

    public function handle(HotelTokenService $hotel, SolanaPaymentService $solana): int
    {
        if (!$hotel->isConfigured()) {
            $this->warn('$HOTEL is not configured yet (set the mint + burn wallet in .env). Nothing to burn.');
            return self::SUCCESS;
        }

        try {
            $balance = $solana->getTokenBalance($hotel->burnWallet(), $hotel->mint());
        } catch (\Throwable $e) {
            $this->error('RPC error reading burn-wallet balance: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Burn wallet {$hotel->burnWallet()} holds {$balance} \$HOTEL.");

        if ($this->option('dry-run') || $balance <= 0) {
            return self::SUCCESS;
        }

        // The SPL burn instruction must be signed by the burn-wallet key. That key custody is an
        // owner decision (recommendation: a dedicated burn wallet holding only fees). Wire the
        // signing here once decided — kept inert so no key is required to run/schedule the command.
        $this->warn('Burn signing is not wired yet — it needs the burn-wallet secret key (custody decision).');
        return self::SUCCESS;
    }
}
