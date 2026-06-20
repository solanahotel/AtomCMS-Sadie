<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\User\PlayerData;
use App\Services\Community\StaffService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    protected array $staffIds = [];

    public function __construct(private readonly StaffService $staffService)
    {
        $this->staffIds = $this->staffService->fetchEmployeeIds();
    }

    public function __invoke(): View
    {
        $limit = (int) config('leaderboard.limit', 10);

        return view('leaderboard', [
            'credits'           => $this->topByColumn('credit_balance', $limit),
            'duckets'           => $this->topByColumn('pixel_balance', $limit),
            'diamonds'          => $this->topByColumn('seasonal_balance', $limit),
            'gotw'              => $this->topByColumn('gotw_points', $limit),
            'respectsReceived'  => $this->topRespectsReceived($limit),
            'achievementScores' => $this->topByColumn('achievement_score', $limit),
        ]);
    }

    /**
     * Base query: non-staff players, joined to `players` so we can use the username as a stable
     * tiebreak, with the player + avatar eager-loaded for the view.
     */
    private function baseQuery(int $limit)
    {
        return PlayerData::query()
            ->whereNotIn('player_data.player_id', $this->staffIds)
            ->join('players', 'players.id', '=', 'player_data.player_id')
            ->select('player_data.*')
            ->take($limit)
            ->with([
                'player:id,username',
                'player.avatar:player_id,figure_code',
            ]);
    }

    /** Top players by a stored player_data column (credits, duckets, score, …). */
    private function topByColumn(string $column, int $limit): Collection
    {
        return $this->baseQuery($limit)
            ->orderByDesc('player_data.' . $column)
            ->orderBy('players.username')
            ->get();
    }

    /**
     * Top players by respects RECEIVED. These live in `player_respects` (one row per respect,
     * keyed by target_player_id) — NOT player_data.respect_points, which is the daily allowance of
     * respects a player can still give. Exposed to the view as the `respects_received` attribute.
     */
    private function topRespectsReceived(int $limit): Collection
    {
        return $this->baseQuery($limit)
            ->selectRaw('(SELECT COUNT(*) FROM player_respects WHERE player_respects.target_player_id = player_data.player_id) AS respects_received')
            ->orderByDesc('respects_received')
            ->orderBy('players.username')
            ->get();
    }
}
