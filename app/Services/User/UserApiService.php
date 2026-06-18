<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserApiService
{
    public function fetchUser(string $username, array $columns): User
    {
        return User::select($columns)->where('username', '=', $username)->first();
    }

    public function onlineUsers($columns = ['username', 'motto', 'look'], bool $randomOrder = true): Builder
    {
        // `players` has no `online` column in Sadie — online status lives in
        // player_data.is_online. Filter via the data relation.
        $query = User::select($columns)->whereHas('data', fn ($q) => $q->where('is_online', 1));

        if ($randomOrder) {
            $query = $query->inRandomOrder();
        }

        return $query;
    }

    public function onlineUserCount(): int
    {
        return User::whereHas('data', fn ($q) => $q->where('is_online', 1))->count();
    }
}
