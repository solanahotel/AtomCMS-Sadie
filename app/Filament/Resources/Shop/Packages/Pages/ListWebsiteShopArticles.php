<?php

namespace App\Filament\Resources\Shop\Packages\Pages;

use App\Filament\Resources\Shop\Packages\WebsiteShopArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteShopArticles extends ListRecords
{
    protected static string $resource = WebsiteShopArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
