<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletChallenge extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'wallet_address',
        'nonce',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}