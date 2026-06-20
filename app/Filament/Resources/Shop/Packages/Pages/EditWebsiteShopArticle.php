<?php

namespace App\Filament\Resources\Shop\Packages\Pages;

use App\Filament\Resources\Shop\Packages\WebsiteShopArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteShopArticle extends EditRecord
{
    protected static string $resource = WebsiteShopArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
