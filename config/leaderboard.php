<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Leaderboard size
    |--------------------------------------------------------------------------
    |
    | How many players to show per leaderboard category. Override per-environment
    | with the LEADERBOARD_LIMIT env var.
    |
    */

    'limit' => (int) env('LEADERBOARD_LIMIT', 10),

];
