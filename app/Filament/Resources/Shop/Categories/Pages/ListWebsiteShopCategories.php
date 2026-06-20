<?php

namespace App\Filament\Resources\Shop\Categories\Pages;

use App\Filament\Resources\Shop\Categories\WebsiteShopCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteShopCategories extends ListRecords
{
    protected static string $resource = WebsiteShopCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
