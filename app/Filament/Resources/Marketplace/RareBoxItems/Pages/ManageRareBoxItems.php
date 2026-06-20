<?php

namespace App\Filament\Resources\Marketplace\RareBoxItems\Pages;

use App\Filament\Resources\Marketplace\RareBoxItems\RareBoxItemResource;
use App\Models\Marketplace\RareBoxItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRareBoxItems extends ManageRecords
{
    protected static string $resource = RareBoxItemResource::class;

    protected function getHeaderActions(): array
    {
        return RareBoxItem::count() < 5 ? [ CreateAction::make() ] : [];
    }
}
