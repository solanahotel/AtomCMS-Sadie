<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

// Anti-farming / anti-Sybil feed: surfaces suspicious patterns for MANUAL review (never auto-actions).
// Built from login-IP capture + the marketplace sale logs + engagement signals.
class AntiFarming extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Anti-Farming';
    protected static ?string $title = 'Anti-Farming — Suspicious Activity';
    protected string $view = 'filament.pages.anti-farming';
    protected static ?string $slug = 'marketplace/anti-farming';

    // Accounts sharing a login IP (each alt costs locked $HOTEL, but clusters are still worth a look).
    public function sharedIps(): array
    {
        return DB::select("
            SELECT li.ip_address,
                   COUNT(DISTINCT li.player_id) AS accounts,
                   GROUP_CONCAT(DISTINCT p.username ORDER BY p.username SEPARATOR ', ') AS usernames,
                   MAX(li.created_at) AS last_seen
            FROM player_login_ips li
            JOIN players p ON p.id = li.player_id
            GROUP BY li.ip_address
            HAVING COUNT(DISTINCT li.player_id) >= 2
            ORDER BY accounts DESC, last_seen DESC
            LIMIT 100");
    }

    // Pairs of accounts that traded with each other in BOTH directions (wash / circular trading).
    public function washTrades(): array
    {
        return DB::select("
            SELECT pa.username AS user_a, pb.username AS user_b, t.ab, t.ba, t.total
            FROM (
                SELECT a, b, SUM(dir_ab) AS ab, SUM(dir_ba) AS ba, COUNT(*) AS total FROM (
                    SELECT LEAST(seller_id, buyer_id) a, GREATEST(seller_id, buyer_id) b,
                           (seller_id < buyer_id) dir_ab, (seller_id > buyer_id) dir_ba
                    FROM item_market_sales WHERE seller_id <> buyer_id
                    UNION ALL
                    SELECT LEAST(seller_id, winner_id), GREATEST(seller_id, winner_id),
                           (seller_id < winner_id), (seller_id > winner_id)
                    FROM auction_sales WHERE seller_id <> winner_id
                ) x GROUP BY a, b HAVING ab > 0 AND ba > 0
            ) t
            JOIN players pa ON pa.id = t.a
            JOIN players pb ON pb.id = t.b
            ORDER BY t.total DESC
            LIMIT 100");
    }

    // Accounts trading with an unusual number of DISTINCT counterparties — possible funnel hub
    // (many alts moving value to/from one account).
    public function funnelAccounts(): array
    {
        return DB::select("
            SELECT p.username, COUNT(DISTINCT t.cp) AS partners, COUNT(*) AS trades
            FROM (
                SELECT buyer_id pid, seller_id cp FROM item_market_sales WHERE seller_id <> buyer_id
                UNION ALL SELECT seller_id, buyer_id FROM item_market_sales WHERE seller_id <> buyer_id
                UNION ALL SELECT winner_id, seller_id FROM auction_sales WHERE seller_id <> winner_id
                UNION ALL SELECT seller_id, winner_id FROM auction_sales WHERE seller_id <> winner_id
            ) t
            JOIN players p ON p.id = t.pid
            GROUP BY p.id, p.username
            HAVING partners >= 5
            ORDER BY partners DESC
            LIMIT 100");
    }

    // New accounts (last 7 days) already pushing a lot of marketplace volume.
    public function newVelocity(): array
    {
        return DB::select("
            SELECT p.id, p.username, p.created_at, COUNT(*) AS trades
            FROM players p
            JOIN (
                SELECT seller_id pid FROM item_market_sales
                UNION ALL SELECT buyer_id FROM item_market_sales
                UNION ALL SELECT seller_id FROM auction_sales
                UNION ALL SELECT winner_id FROM auction_sales
            ) t ON t.pid = p.id
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY p.id, p.username, p.created_at
            HAVING trades >= 5
            ORDER BY trades DESC
            LIMIT 100");
    }

    // Behavioral: logs in repeatedly but shows near-zero genuine engagement (no friends/rooms/chat).
    public function lowEngagement(): array
    {
        return DB::select("
            SELECT p.id, p.username,
                (SELECT COUNT(*) FROM player_login_ips li WHERE li.player_id = p.id) AS logins,
                (SELECT COUNT(*) FROM player_friendships f WHERE f.origin_player_id = p.id OR f.target_player_id = p.id) AS friends,
                (SELECT COUNT(*) FROM rooms r WHERE r.owner_id = p.id) AS rooms,
                (SELECT COUNT(*) FROM room_chat_messages c WHERE c.player_id = p.id) AS chats
            FROM players p
            HAVING logins >= 5 AND friends = 0 AND rooms = 0 AND chats <= 2
            ORDER BY logins DESC
            LIMIT 100");
    }
}
