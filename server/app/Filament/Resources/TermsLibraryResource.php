<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermsLibraryResource\Pages;
use App\Models\TermsLibrary;
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

class TermsLibraryResource extends Resource
{
    protected static ?string $model = TermsLibrary::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('scope_items') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationParentItem = 'Proposals';

    protected static ?string $navigationLabel = 'Terms Library';

    protected static ?string $modelLabel = 'Terms Library Item';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(6)
                            ->helperText('This term will be added to new proposals generated from Screenah.')
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
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->wrap()
                    ->limit(180)
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index' => Pages\ListTermsLibrary::route('/'),
            'create' => Pages\CreateTermsLibraryItem::route('/create'),
            'edit' => Pages\EditTermsLibraryItem::route('/{record}/edit'),
        ];
    }
}
