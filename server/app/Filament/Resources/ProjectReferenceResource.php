<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectReferenceResource\Pages;
use App\Models\ProjectReference;
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

class ProjectReferenceResource extends Resource
{
    protected static ?string $model = ProjectReference::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('proposals') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationParentItem = 'Proposals';

    protected static ?string $navigationLabel = 'References';

    protected static ?string $modelLabel = 'Reference';

    protected static ?string $pluralModelLabel = 'References';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title')
                            ->maxLength(255)
                            ->placeholder('e.g. CTO, Director of Operations'),
                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                    ]),
                Section::make('Engagement')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('project_name')
                            ->maxLength(255)
                            ->placeholder('e.g. LUMA Citizen Management Portal'),
                        Forms\Components\TextInput::make('project_location')
                            ->maxLength(255)
                            ->placeholder('e.g. Town of Bridgewater, VA'),
                        Forms\Components\Textarea::make('project_description')
                            ->rows(4)
                            ->placeholder('Brief description of the project divStrong delivered for this reference...')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('year_completed')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue((int) date('Y') + 1)
                            ->placeholder('e.g. 2024'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive references are hidden from the proposal attachment picker.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('year_completed')
                    ->label('Year')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('proposals_count')
                    ->label('Used On')
                    ->counts('proposals')
                    ->badge()
                    ->toggleable(),
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
            'index' => Pages\ListProjectReferences::route('/'),
            'create' => Pages\CreateProjectReference::route('/create'),
            'edit' => Pages\EditProjectReference::route('/{record}/edit'),
        ];
    }
}
