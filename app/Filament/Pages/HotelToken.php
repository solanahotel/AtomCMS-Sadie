<?php

namespace App\Filament\Pages;

use App\Services\HotelTokenService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

// Config status + checklist for the $HOTEL token layer. Everything is .env-driven (config/solana.php);
// this page shows what's set, what's missing, and whether the gate / exchange are live.
class HotelToken extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = '$HOTEL / Crypto';
    protected static ?string $title = '$HOTEL Token & Credit Exchange';
    protected string $view = 'filament.pages.hotel-token';
    protected static ?string $slug = 'marketplace/hotel-token';

    public function token(): HotelTokenService
    {
        return app(HotelTokenService::class);
    }

    public function swaps(): array
    {
        return DB::select("
            SELECT s.id, s.listing_id, s.amount_hotel, s.fee_hotel, s.tx_signature, s.status, s.created_at,
                   pb.username AS buyer, ps.username AS seller
            FROM credit_exchange_swaps s
            LEFT JOIN players pb ON pb.id = s.buyer_id
            LEFT JOIN players ps ON ps.id = s.seller_id
            ORDER BY s.created_at DESC LIMIT 50");
    }
}
