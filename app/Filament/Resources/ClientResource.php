<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Proposals';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Client')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('company')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('domain')
                                            ->maxLength(255)
                                            ->prefix('https://'),
                                    ]),
                                Section::make('Address')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('address1')
                                            ->label('Address Line 1')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('address2')
                                            ->label('Address Line 2')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('zip')
                                            ->label('ZIP Code')
                                            ->maxLength(20),
                                    ]),
                            ]),

                        Tab::make('Proposals')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Livewire::make(\App\Livewire\ClientProposals::class)
                                    ->data(fn ($record) => [
                                        'clientId' => $record?->id,
                                    ]),
                            ])
                            ->visible(fn ($record) => $record !== null),

                        Tab::make('Notes')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Livewire::make(\App\Livewire\ClientNotes::class)
                                    ->data(fn ($record) => [
                                        'clientId' => $record?->id,
                                    ]),
                            ])
                            ->visible(fn ($record) => $record !== null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('proposals_count')
                    ->counts('proposals')
                    ->label('Proposals')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([])
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
