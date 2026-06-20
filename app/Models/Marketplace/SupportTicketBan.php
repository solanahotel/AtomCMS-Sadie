<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class SupportTicketBan extends Model
{
    protected $table = 'support_ticket_bans';
    public $timestamps = false;
    protected $primaryKey = 'player_id';
    public $incrementing = false;
    protected $fillable = ['player_id', 'reason', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
