<?php

namespace App\Filament\Resources\Marketplace\SupportTickets\Pages;

use App\Filament\Resources\Marketplace\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ManageSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
    protected function getHeaderActions(): array { return []; }
}
