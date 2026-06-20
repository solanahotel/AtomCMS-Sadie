<?php

namespace App\Filament\Resources\Marketplace\ItemMarketSales\Pages;

use App\Filament\Resources\Marketplace\ItemMarketSales\ItemMarketSaleResource;
use Filament\Resources\Pages\ListRecords;

class ListItemMarketSales extends ListRecords
{
    protected static string $resource = ItemMarketSaleResource::class;
    protected function getHeaderActions(): array { return []; }
}
