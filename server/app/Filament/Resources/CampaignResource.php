<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Drip campaigns: the sequence, its steps, and how each one is performing.
 *
 * Steps are edited inline as a repeater rather than as their own resource — a step has no
 * meaning outside its campaign, and reordering four of them across two pages is worse than
 * doing it in one.
 */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('prospects') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?string $modelLabel = 'Campaign';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set, ?Campaign $record) => $record
                            ? null
                            : $set('key', Str::slug((string) $state))),

                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->maxLength(60)
                        ->unique(ignoreRecord: true)
                        ->helperText('Stable identifier. Changing it on a live campaign is safe but pointless.'),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Pausing stops every enrollment in this campaign from sending, without ending them.')
                        ->default(true),

                    Forms\Components\Toggle::make('stop_on_booking')
                        ->label('Stop when they book a call')
                        ->default(true),

                    Forms\Components\Toggle::make('stop_on_reply')
                        ->label('Stop when they reply')
                        ->helperText('Replies are not detected automatically yet — stop these by hand from the prospect.')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Steps')
                ->description('Delays are counted from the previous step, so 0 / 3 / 4 / 7 sends on days 0, 3, 7 and 14.')
                ->schema([
                    Forms\Components\Repeater::make('steps')
                        ->relationship()
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(80),

                            Forms\Components\Select::make('template_key')
                                ->label('Email template')
                                ->options(fn () => EmailTemplate::query()->orderBy('name')->pluck('name', 'key'))
                                ->searchable()
                                ->required()
                                ->helperText('Edit the words in Email Templates.'),

                            Forms\Components\TextInput::make('delay_days')
                                ->label('Days after previous step')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(120)
                                ->default(3)
                                ->required(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->helperText('An inactive step is skipped without ending the sequence.'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add a step')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Campaign $record): ?string => $record->description
                        ? Str::limit($record->description, 70)
                        : null)
                    ->searchable(),

                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Steps')
                    ->counts('steps')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('active_count')
                    ->label('In flight')
                    ->alignRight()
                    ->state(fn (Campaign $record): int => $record->enrollments()
                        ->where('status', CampaignEnrollment::STATUS_ACTIVE)->count()),

                Tables\Columns\TextColumn::make('booked_count')
                    ->label('Booked')
                    ->alignRight()
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->state(fn (Campaign $record): int => $record->enrollments()
                        ->where('stop_reason', CampaignEnrollment::STOP_BOOKED)->count()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
