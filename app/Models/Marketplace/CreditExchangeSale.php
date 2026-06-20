<?php

namespace App\Models\Marketplace;

use App\Models\Game\FurnitureItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditExchangeSale extends Model
{
    protected $table = 'credit_exchange_sales';
    public $timestamps = false;
    protected $casts = ['sold_at' => 'datetime'];

    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function item(): BelongsTo { return $this->belongsTo(FurnitureItem::class, 'furniture_item_id'); }
}
