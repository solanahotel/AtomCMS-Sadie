<?php

namespace App\Filament\Resources\Marketplace\AuctionSales\Pages;

use App\Filament\Resources\Marketplace\AuctionSales\AuctionSaleResource;
use Filament\Resources\Pages\ListRecords;

class ListAuctionSales extends ListRecords
{
    protected static string $resource = AuctionSaleResource::class;
    protected function getHeaderActions(): array { return []; }
}
