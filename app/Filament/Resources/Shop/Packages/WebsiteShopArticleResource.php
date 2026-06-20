<?php

namespace App\Filament\Resources\Shop\Packages;

use App\Filament\Resources\Shop\Packages\Pages\CreateWebsiteShopArticle;
use App\Filament\Resources\Shop\Packages\Pages\EditWebsiteShopArticle;
use App\Filament\Resources\Shop\Packages\Pages\ListWebsiteShopArticles;
use App\Models\Shop\WebsiteShopArticle;
use App\Models\Shop\WebsiteShopCategory;
use App\Models\User\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebsiteShopArticleResource extends Resource
{
    protected static ?string $model = WebsiteShopArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Shop Packages';

    protected static ?string $slug = 'shop/packages';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Main')
                    ->tabs([
                        Tab::make('Package')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Select::make('website_shop_category_id')
                                    ->label('Category')
                                    ->options(fn () => WebsiteShopCategory::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->required(),

                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('info')
                                    ->label('Short description')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('icon_url')
                                    ->label('Icon URL')
                                    ->helperText('Image shown on the card, e.g. /assets/images/icons/credits.png')
                                    ->maxLength(255),

                                TextInput::make('color')
                                    ->label('Accent colour')
                                    ->helperText('CSS colour for the card header, e.g. #f5a623')
                                    ->maxLength(255),

                                TextInput::make('costs')
                                    ->label('Cost (in cents)')
                                    ->helperText('Charged from website balance. 100 = $1.00 (e.g. 500 = $5.00).')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('position')
                                    ->label('Position')
                                    ->helperText('Lower numbers appear first.')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                Toggle::make('is_giftable')
                                    ->label('Giftable')
                                    ->helperText('Allow buying this package for another player.')
                                    ->default(true),
                            ])->columns(2),

                        Tab::make('Rewards')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                TextInput::make('credits')->label('Credits')->numeric()->minValue(0)->default(0),
                                TextInput::make('duckets')->label('Duckets')->numeric()->minValue(0)->default(0),
                                TextInput::make('diamonds')->label('Diamonds')->numeric()->minValue(0)->default(0),

                                Select::make('give_rank')
                                    ->label('Give rank')
                                    ->options(fn () => Role::orderBy('id')->pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('None')
                                    ->helperText('Grants this rank on purchase. Leave empty unless you have a dedicated VIP role — never sell a staff rank.'),

                                TextInput::make('badges')
                                    ->label('Badges')
                                    ->helperText('Semicolon-separated badge codes, e.g. ACH_BasicClub5;NL388. Codes must exist in the badges table.')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Textarea::make('furniture')
                                    ->label('Furniture (JSON)')
                                    ->helperText('Optional. JSON array, e.g. [{"item_id":123,"amount":1}]')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(3),

                        Tab::make('Features')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('features')
                                    ->relationship()
                                    ->label('Feature bullets')
                                    ->schema([
                                        TextInput::make('content')
                                            ->label('Feature')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->orderColumn('id')
                                    ->reorderable(false)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                ImageColumn::make('icon_url')->label('Icon')->height(32),
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->badge()->sortable(),
                TextColumn::make('price')->label('Price')->state(fn (WebsiteShopArticle $r) => '$' . number_format($r->price(), 2)),
                TextColumn::make('credits')->label('Credits')->toggleable(),
                TextColumn::make('duckets')->label('Duckets')->toggleable(),
                TextColumn::make('diamonds')->label('Diamonds')->toggleable(),
                IconColumn::make('is_giftable')->label('Gift')->boolean(),
                TextColumn::make('position')->label('Pos')->sortable(),
            ])
            ->filters([
                SelectFilter::make('website_shop_category_id')
                    ->label('Category')
                    ->options(fn () => WebsiteShopCategory::orderBy('name')->pluck('name', 'id'))
                    ->placeholder('All'),
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
            'index' => ListWebsiteShopArticles::route('/'),
            'create' => CreateWebsiteShopArticle::route('/create'),
            'edit' => EditWebsiteShopArticle::route('/{record}/edit'),
        ];
    }
}
