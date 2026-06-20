<?php

namespace App\Filament\Resources\Marketplace\ItemMarketSales;

use App\Filament\Resources\Marketplace\ItemMarketSales\Pages\ListItemMarketSales;
use App\Models\Marketplace\ItemMarketSale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemMarketSaleResource extends Resource
{
    protected static ?string $model = ItemMarketSale::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Item Market Sales';
    protected static ?string $slug = 'marketplace/item-market-sales';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sold_at', 'desc')->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('quantity')->label('Qty'),
            TextColumn::make('seller.username')->label('Seller')->searchable(),
            TextColumn::make('buyer.username')->label('Buyer')->searchable(),
            TextColumn::make('price_credits')->label('Price (credits)')->sortable(),
            TextColumn::make('fee_credits')->label('Fee burned')->sortable(),
            TextColumn::make('sold_at')->label('Sold')->dateTime('Y-m-d H:i')->sortable(),
        ])->filters([])->recordActions([])->toolbarActions([]);
    }

    public static function getPages(): array { return ['index' => ListItemMarketSales::route('/')]; }
}
