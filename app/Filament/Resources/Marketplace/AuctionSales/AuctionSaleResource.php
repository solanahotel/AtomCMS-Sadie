<?php

namespace App\Filament\Resources\Marketplace\AuctionSales;

use App\Filament\Resources\Marketplace\AuctionSales\Pages\ListAuctionSales;
use App\Models\Marketplace\AuctionSale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuctionSaleResource extends Resource
{
    protected static ?string $model = AuctionSale::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Auction Sales';
    protected static ?string $slug = 'marketplace/auction-sales';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sold_at', 'desc')->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('seller.username')->label('Seller')->searchable(),
            TextColumn::make('winner.username')->label('Winner')->searchable(),
            TextColumn::make('final_bid')->label('Final bid (credits)')->sortable(),
            TextColumn::make('fee_credits')->label('Fee burned')->sortable(),
            TextColumn::make('sold_at')->label('Sold')->dateTime('Y-m-d H:i')->sortable(),
        ])->filters([])->recordActions([])->toolbarActions([]);
    }

    public static function getPages(): array { return ['index' => ListAuctionSales::route('/')]; }
}
