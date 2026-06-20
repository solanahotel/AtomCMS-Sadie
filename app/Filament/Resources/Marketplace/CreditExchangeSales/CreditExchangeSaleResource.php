<?php

namespace App\Filament\Resources\Marketplace\CreditExchangeSales;

use App\Filament\Resources\Marketplace\CreditExchangeSales\Pages\ListCreditExchangeSales;
use App\Models\Marketplace\CreditExchangeSale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditExchangeSaleResource extends Resource
{
    protected static ?string $model = CreditExchangeSale::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Credit Exchange Sales';
    protected static ?string $slug = 'marketplace/credit-exchange-sales';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sold_at', 'desc')->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('quantity')->label('Qty'),
            TextColumn::make('total_credit_value')->label('Credit value')->sortable(),
            TextColumn::make('seller.username')->label('Seller')->searchable(),
            TextColumn::make('buyer.username')->label('Buyer')->searchable(),
            TextColumn::make('price_hotel')->label('$HOTEL price')->sortable(),
            TextColumn::make('fee_hotel')->label('Fee ($HOTEL)'),
            TextColumn::make('tx_signature')->label('Tx')->limit(14)->tooltip(fn ($state) => $state),
            TextColumn::make('sold_at')->label('Sold')->dateTime('Y-m-d H:i')->sortable(),
        ])->filters([])->recordActions([])->toolbarActions([]);
    }

    public static function getPages(): array { return ['index' => ListCreditExchangeSales::route('/')]; }
}
