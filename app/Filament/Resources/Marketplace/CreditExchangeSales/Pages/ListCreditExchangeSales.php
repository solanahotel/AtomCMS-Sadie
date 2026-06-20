<?php

namespace App\Filament\Resources\Marketplace\CreditExchangeSales\Pages;

use App\Filament\Resources\Marketplace\CreditExchangeSales\CreditExchangeSaleResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditExchangeSales extends ListRecords
{
    protected static string $resource = CreditExchangeSaleResource::class;
    protected function getHeaderActions(): array { return []; }
}
