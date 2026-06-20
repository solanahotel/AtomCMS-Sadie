<?php

namespace App\Services;

// $HOTEL access gate: a wallet must hold >= the configured amount to play. DISABLED by default
// (config), so allows() returns true for everyone until the owner turns it on. Fail-open on RPC
// errors so a flaky node never locks the hotel out.
class AccessGateService
{
    public function __construct(
        private readonly HotelTokenService $hotel,
        private readonly SolanaPaymentService $solana,
    ) {
    }

    public function isActive(): bool
    {
        return $this->hotel->isAccessGateActive();
    }

    public function allows(?string $walletAddress): bool
    {
        if (!$this->hotel->isAccessGateActive()) {
            return true; // gate disabled -> never blocks
        }

        if (empty($walletAddress)) {
            return false; // gate active but no linked wallet
        }

        try {
            return $this->solana->getTokenBalance($walletAddress, $this->hotel->mint()) >= $this->hotel->accessGateAmount();
        } catch (\Throwable $e) {
            return true; // RPC trouble -> fail-open (don't lock players out)
        }
    }
}
