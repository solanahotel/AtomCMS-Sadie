<?php

namespace App\Models\Marketplace;

use App\Models\Game\FurnitureItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RareBoxItem extends Model
{
    protected $table = 'rare_box_items';
    public $timestamps = false;
    protected $fillable = ['furniture_item_id'];

    public function item(): BelongsTo { return $this->belongsTo(FurnitureItem::class, 'furniture_item_id'); }
}
