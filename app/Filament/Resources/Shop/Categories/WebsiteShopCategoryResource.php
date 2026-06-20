<?php

namespace App\Filament\Resources\Shop\Categories;

use App\Filament\Resources\Shop\Categories\Pages\CreateWebsiteShopCategory;
use App\Filament\Resources\Shop\Categories\Pages\EditWebsiteShopCategory;
use App\Filament\Resources\Shop\Categories\Pages\ListWebsiteShopCategories;
use App\Models\Shop\WebsiteShopCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WebsiteShopCategoryResource extends Resource
{
    protected static ?string $model = WebsiteShopCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Shop Categories';

    protected static ?string $slug = 'shop/categories';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('URL-safe identifier used in /shop/{slug} (e.g. "credits").')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('icon')
                    ->label('Icon URL')
                    ->helperText('Image shown next to the category, e.g. /assets/images/icons/credits.png')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                ImageColumn::make('icon')->label('Icon')->height(32),
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('articles_count')->counts('articles')->label('Packages'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteShopCategories::route('/'),
            'create' => CreateWebsiteShopCategory::route('/create'),
            'edit' => EditWebsiteShopCategory::route('/{record}/edit'),
        ];
    }
}
