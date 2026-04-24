<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioItemResource\Pages;
use App\Models\PortfolioItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PortfolioItemResource extends Resource
{
    protected static ?string $model = PortfolioItem::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('proposals') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Portfolio';

    protected static ?string $modelLabel = 'Portfolio Item';

    protected static ?string $pluralModelLabel = 'Portfolio Items';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('url')
                            ->label('Project URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://...'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('technologies')
                            ->maxLength(255)
                            ->placeholder('e.g. Laravel, Livewire, Tailwind')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->directory('portfolio')
                            ->disk('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),
                Section::make('Visibility')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive items are hidden from the proposal attachment picker.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->getStateUsing(fn (PortfolioItem $r) => $r->image_url)
                    ->size(60),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('url')
                    ->limit(40)
                    ->url(fn (PortfolioItem $r) => $r->url, true)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('technologies')
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('proposals_count')
                    ->label('Used On')
                    ->counts('proposals')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolioItems::route('/'),
            'create' => Pages\CreatePortfolioItem::route('/create'),
            'edit' => Pages\EditPortfolioItem::route('/{record}/edit'),
        ];
    }
}
