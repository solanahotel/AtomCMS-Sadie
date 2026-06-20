<?php

namespace App\Filament\Resources\Marketplace\DailyQuests\Pages;

use App\Filament\Resources\Marketplace\DailyQuests\DailyQuestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDailyQuests extends ManageRecords
{
    protected static string $resource = DailyQuestResource::class;

    protected function getHeaderActions(): array { return [ CreateAction::make() ]; }
}
