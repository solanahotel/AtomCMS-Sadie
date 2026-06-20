<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $table = 'support_ticket_messages';
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'sender_id', 'sender_name', 'is_staff', 'body', 'created_at'];
    protected $casts = ['is_staff' => 'boolean', 'created_at' => 'datetime'];
}
