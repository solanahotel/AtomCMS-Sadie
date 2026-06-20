<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';
    public $timestamps = false;
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function player(): BelongsTo { return $this->belongsTo(User::class, 'player_id'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('id'); }
    public function isRestricted(): bool { return SupportTicketBan::where('player_id', $this->player_id)->exists(); }
}
