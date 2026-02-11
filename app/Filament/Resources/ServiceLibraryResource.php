<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceLibraryResource\Pages;
use App\Models\ServiceLibrary;
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

class ServiceLibraryResource extends Resource
{
    protected static ?string $model = ServiceLibrary::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Libraries';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Service Library Item';

    protected static ?int $navigationSort = 2;

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
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('default_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\Select::make('unit')
                            ->options([
                                'fixed' => 'Fixed',
                                'hourly' => 'Hourly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('fixed')
                            ->required(),
                        Forms\Components\TextInput::make('default_quantity')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_price')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
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
            'index' => Pages\ListServiceLibrary::route('/'),
            'create' => Pages\CreateServiceLibraryItem::route('/create'),
            'edit' => Pages\EditServiceLibraryItem::route('/{record}/edit'),
        ];
    }
}
