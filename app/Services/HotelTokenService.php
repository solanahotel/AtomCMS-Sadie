<?php

namespace App\Services;

// Central accessor + status for the $HOTEL token layer (Phase 5). All values come from
// config/solana.php (env-driven). Until the mint + wallets are configured, both the access gate
// and the Credit Exchange settlement report themselves as inactive, so nothing fires early.
class HotelTokenService
{
    public function mint(): string { return (string) config('solana.hotel.mint'); }
    public function decimals(): int { return (int) config('solana.hotel.decimals'); }
    public function treasuryWallet(): string { return (string) config('solana.hotel.treasury_wallet'); }
    public function burnWallet(): string { return (string) config('solana.hotel.burn_wallet'); }
    public function feePercent(): float { return (float) config('solana.hotel.fee_percent'); }
    public function rpcUrl(): string { return (string) config('solana.rpc_url'); }
    public function clientRpcUrl(): string { return (string) config('solana.client_rpc_url'); }

    public function accessGateEnabled(): bool { return (bool) config('solana.hotel.access_gate.enabled'); }
    public function accessGateAmount(): int { return (int) config('solana.hotel.access_gate.amount'); }
    public function exchangeEnabled(): bool { return (bool) config('solana.hotel.exchange_enabled'); }

    // True once the on-chain essentials are filled in.
    public function isConfigured(): bool
    {
        return $this->mint() !== '' && $this->treasuryWallet() !== '' && $this->burnWallet() !== '';
    }

    // The Credit Exchange only settles on-chain when explicitly enabled AND fully configured.
    public function isExchangeLive(): bool
    {
        return $this->exchangeEnabled() && $this->isConfigured();
    }

    // The access gate only blocks play when explicitly enabled AND a mint is set.
    public function isAccessGateActive(): bool
    {
        return $this->accessGateEnabled() && $this->mint() !== '';
    }

    // Split a gross $HOTEL amount into (seller proceeds, fee) using the configured fee percent.
    public function splitFee(float $amount): array
    {
        $fee = round($amount * ($this->feePercent() / 100), $this->decimals());
        return ['seller' => round($amount - $fee, $this->decimals()), 'fee' => $fee];
    }

    // Convert a human $HOTEL amount to base units (for SPL transfers).
    public function toBaseUnits(float $amount): string
    {
        return bcmul((string) $amount, bcpow('10', (string) $this->decimals()), 0);
    }

    // Per-field config status for the admin checklist page.
    public function checklist(): array
    {
        return [
            ['label' => 'Mint address',     'value' => $this->mint(),            'env' => 'HOTEL_MINT'],
            ['label' => 'Token decimals',   'value' => (string) $this->decimals(), 'env' => 'HOTEL_DECIMALS'],
            ['label' => 'Treasury wallet',  'value' => $this->treasuryWallet(),  'env' => 'HOTEL_TREASURY_WALLET'],
            ['label' => 'Burn wallet',      'value' => $this->burnWallet(),      'env' => 'HOTEL_BURN_WALLET'],
            ['label' => 'Fee percent',      'value' => $this->feePercent() . '%', 'env' => 'HOTEL_FEE_PERCENT'],
            ['label' => 'Server RPC URL',   'value' => $this->rpcUrl(),          'env' => 'SOLANA_RPC_URL'],
            ['label' => 'Client RPC URL',   'value' => $this->clientRpcUrl(),    'env' => 'SOLANA_CLIENT_RPC_URL'],
        ];
    }
}
