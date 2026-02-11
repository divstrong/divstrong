<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScopeLibraryResource\Pages;
use App\Models\ScopeLibrary;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ScopeLibraryResource extends Resource
{
    protected static ?string $model = ScopeLibrary::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Libraries';

    protected static ?string $navigationLabel = 'Scope Items';

    protected static ?string $modelLabel = 'Scope Library Item';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->options([
                                'Design' => 'Design',
                                'Development' => 'Development',
                                'SEO' => 'SEO',
                                'Hosting' => 'Hosting',
                                'Content' => 'Content',
                                'Maintenance' => 'Maintenance',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Design' => 'Design',
                        'Development' => 'Development',
                        'SEO' => 'SEO',
                        'Hosting' => 'Hosting',
                        'Content' => 'Content',
                        'Maintenance' => 'Maintenance',
                    ]),
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
            'index' => Pages\ListScopeLibrary::route('/'),
            'create' => Pages\CreateScopeLibraryItem::route('/create'),
            'edit' => Pages\EditScopeLibraryItem::route('/{record}/edit'),
        ];
    }
}
