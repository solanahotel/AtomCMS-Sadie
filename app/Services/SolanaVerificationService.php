<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SolanaVerificationService
{
    public function verifySignature(
        string $walletAddress,
        string $message,
        string $signature
    ): bool {
        try {
            $publicKeyBytes = $this->base58Decode($walletAddress);
            $signatureBytes = base64_decode($signature);
            $messageBytes   = $message;

            if (strlen($publicKeyBytes) !== 32) {
                Log::warning('Invalid public key length', ['address' => $walletAddress]);
                return false;
            }

            if (strlen($signatureBytes) !== 64) {
                Log::warning('Invalid signature length');
                return false;
            }

            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                Log::error('sodium extension not available');
                return false;
            }

            return sodium_crypto_sign_verify_detached(
                $signatureBytes,
                $messageBytes,
                $publicKeyBytes
            );
        } catch (\Throwable $e) {
            Log::error('Solana signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function base58Decode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base     = strlen($alphabet);
        $bytes    = [0];

        for ($i = 0; $i < strlen($input); $i++) {
            $char = strpos($alphabet, $input[$i]);
            if ($char === false) {
                throw new \InvalidArgumentException('Invalid base58 character');
            }
            $carry = $char;
            for ($j = count($bytes) - 1; $j >= 0; $j--) {
                $carry     += $base * $bytes[$j];
                $bytes[$j] = $carry % 256;
                $carry     = (int) ($carry / 256);
            }
            while ($carry > 0) {
                array_unshift($bytes, $carry % 256);
                $carry = (int) ($carry / 256);
            }
        }

        foreach (str_split($input) as $char) {
            if ($char !== '1') break;
            array_unshift($bytes, 0);
        }

        return implode('', array_map('chr', $bytes));
    }

    public function isValidWalletAddress(string $address): bool
    {
        if (strlen($address) < 32 || strlen($address) > 44) {
            return false;
        }
        $validChars = '/^[123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz]+$/';
        return (bool) preg_match($validChars, $address);
    }
}
