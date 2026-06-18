<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubPayment extends Model
{
    protected $table = 'club_payments';

    protected $fillable = [
        'player_id', 'wallet_address', 'package_id', 'reference', 'expected_lamports',
        'price_usd', 'sol_usd_rate', 'signature', 'status', 'treasury_wallet', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];
}
