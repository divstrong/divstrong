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
use Filament\Schemas\Components\Section;
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
                Section::make()
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
                        Forms\Components\Repeater::make('bullets')
                            ->label('Bullet Points')
                            ->simple(
                                Forms\Components\TextInput::make('bullet')
                                    ->required()
                                    ->placeholder('Bullet point text'),
                            )
                            ->defaultItems(1)
                            ->addActionLabel('Add bullet')
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
                Tables\Columns\TextColumn::make('bullets')
                    ->label('Bullets')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' bullet' . (count($state) !== 1 ? 's' : '') : '—')
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
