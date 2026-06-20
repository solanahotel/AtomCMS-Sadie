<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class DailyQuest extends Model
{
    protected $table = 'daily_quests';
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'description', 'goal', 'reward_credits', 'enabled', 'sort_order'];
    protected $casts = ['enabled' => 'boolean'];
}
