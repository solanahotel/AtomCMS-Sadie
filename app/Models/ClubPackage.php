<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubPackage extends Model
{
    protected $table = 'club_packages';

    protected $fillable = ['name', 'months', 'duration_days', 'price_usd', 'order_num', 'enabled'];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'enabled' => 'boolean',
    ];
}
