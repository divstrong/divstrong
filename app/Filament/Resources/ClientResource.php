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

    protected static ?string $modelLabel = 'Client';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

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
                                        Forms\Components\TextInput::make('title')
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
                                        Forms\Components\FileUpload::make('logo')
                                            ->label('Avatar')
                                            ->image()
                                            ->directory('client-logos')
                                            ->imageResizeMode('contain')
                                            ->maxSize(1024)
                                            ->columnSpanFull(),
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
                                        Forms\Components\Select::make('state')
                                            ->searchable()
                                            ->options([
                                                'AL' => 'Alabama',
                                                'AK' => 'Alaska',
                                                'AZ' => 'Arizona',
                                                'AR' => 'Arkansas',
                                                'CA' => 'California',
                                                'CO' => 'Colorado',
                                                'CT' => 'Connecticut',
                                                'DE' => 'Delaware',
                                                'FL' => 'Florida',
                                                'GA' => 'Georgia',
                                                'HI' => 'Hawaii',
                                                'ID' => 'Idaho',
                                                'IL' => 'Illinois',
                                                'IN' => 'Indiana',
                                                'IA' => 'Iowa',
                                                'KS' => 'Kansas',
                                                'KY' => 'Kentucky',
                                                'LA' => 'Louisiana',
                                                'ME' => 'Maine',
                                                'MD' => 'Maryland',
                                                'MA' => 'Massachusetts',
                                                'MI' => 'Michigan',
                                                'MN' => 'Minnesota',
                                                'MS' => 'Mississippi',
                                                'MO' => 'Missouri',
                                                'MT' => 'Montana',
                                                'NE' => 'Nebraska',
                                                'NV' => 'Nevada',
                                                'NH' => 'New Hampshire',
                                                'NJ' => 'New Jersey',
                                                'NM' => 'New Mexico',
                                                'NY' => 'New York',
                                                'NC' => 'North Carolina',
                                                'ND' => 'North Dakota',
                                                'OH' => 'Ohio',
                                                'OK' => 'Oklahoma',
                                                'OR' => 'Oregon',
                                                'PA' => 'Pennsylvania',
                                                'RI' => 'Rhode Island',
                                                'SC' => 'South Carolina',
                                                'SD' => 'South Dakota',
                                                'TN' => 'Tennessee',
                                                'TX' => 'Texas',
                                                'UT' => 'Utah',
                                                'VT' => 'Vermont',
                                                'VA' => 'Virginia',
                                                'WA' => 'Washington',
                                                'WV' => 'West Virginia',
                                                'WI' => 'Wisconsin',
                                                'WY' => 'Wyoming',
                                                'DC' => 'District of Columbia',
                                            ]),
                                        Forms\Components\TextInput::make('zip')
                                            ->label('ZIP Code')
                                            ->maxLength(20),
                                    ]),
                            ]),

                        Tab::make('Featured Work')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Section::make('Feature this client on the homepage')
                                    ->description('Fill in these fields to showcase this client in the Featured Work section.')
                                    ->schema([
                                        Forms\Components\FileUpload::make('feature_image')
                                            ->label('Project Image')
                                            ->image()
                                            ->directory('featured-work')
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('16:9')
                                            ->maxSize(2048),
                                        Forms\Components\TextInput::make('feature_title')
                                            ->label('Project Title')
                                            ->maxLength(255)
                                            ->placeholder('e.g. E-Commerce Platform Redesign'),
                                        Forms\Components\Textarea::make('feature_description')
                                            ->label('Project Description')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Brief description of the work done for this client...'),
                                        Forms\Components\TagsInput::make('feature_tags')
                                            ->label('Service Tags')
                                            ->placeholder('Add a tag')
                                            ->suggestions(['Strategy', 'Design', 'Coding', 'Hosting']),
                                        Forms\Components\TextInput::make('feature_url')
                                            ->label('Project URL')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255)
                                            ->placeholder('www.example.com'),
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
