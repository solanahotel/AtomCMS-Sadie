<?php

namespace App\Filament\Resources\Marketplace\RareBoxItems;

use App\Filament\Resources\Marketplace\RareBoxItems\Pages\ManageRareBoxItems;
use App\Models\Game\FurnitureItem;
use App\Models\Marketplace\RareBoxItem;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RareBoxItemResource extends Resource
{
    protected static ?string $model = RareBoxItem::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Rare Box Items';
    protected static ?string $slug = 'marketplace/rare-box-items';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('furniture_item_id')
                ->label('Item')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => FurnitureItem::query()
                    ->where('marketable', 1)
                    ->where('name', 'like', "%{$search}%")
                    ->limit(50)->pluck('name', 'id')->toArray())
                ->getOptionLabelUsing(fn ($value) => FurnitureItem::find($value)?->name)
                ->helperText('Pick a marketable item — the winner receives a tradeable copy. Keep ~5 items for 20% each.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('item.name')->label('Item')->searchable(),
            TextColumn::make('furniture_item_id')->label('Furni ID'),
        ])->recordActions([ DeleteAction::make() ])->toolbarActions([]);
    }

    public static function canCreate(): bool { return RareBoxItem::count() < 5; }

    public static function getPages(): array { return ['index' => ManageRareBoxItems::route('/')]; }
}
