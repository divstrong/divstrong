<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Models\Meeting;
use App\Support\BookingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Calls booked from a preview page or the public booking page.
 *
 * Read-mostly on purpose: times are chosen by the person who has to attend, and an admin
 * quietly moving one is how somebody dials in to an empty line. Cancelling is offered
 * because that at least emails both sides.
 */
class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('prospects') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Booked Calls';

    protected static ?string $modelLabel = 'Call';

    protected static ?int $navigationSort = 7;

    /** Upcoming calls, so the sidebar says how many are actually coming. */
    public static function getNavigationBadge(): ?string
    {
        $count = Meeting::query()->upcoming()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('When')
                    ->dateTime('D M j, g:i A')
                    ->timezone(config('scheduling.timezone'))
                    ->description(fn (Meeting $record): string => 'Their time: ' . $record->readableTime())
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Who')
                    ->weight('semibold')
                    ->description(fn (Meeting $record): ?string => $record->company)
                    ->searchable(['name', 'company', 'email']),

                Tables\Columns\TextColumn::make('email')
                    ->label('Contact')
                    ->description(fn (Meeting $record): ?string => $record->phone)
                    ->copyable(),

                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'preview' => 'Preview page',
                        'public' => 'Booking page',
                        default => ucfirst($state),
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Meeting $record): string => match (true) {
                        $record->isCanceled() => 'danger',
                        $record->isPast() => 'gray',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (Meeting $record): string => match (true) {
                        $record->isCanceled() => 'Canceled',
                        $record->isPast() => 'Done',
                        default => 'Booked',
                    }),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->upcoming())
                    ->default(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Meeting::STATUS_BOOKED => 'Booked',
                        Meeting::STATUS_CANCELED => 'Canceled',
                    ]),
            ])
            ->actions([
                Action::make('openProspect')
                    ->label('Prospect')
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->visible(fn (Meeting $record): bool => $record->prospect_id !== null)
                    ->url(fn (Meeting $record): string => ProspectResource::getUrl('edit', ['record' => $record->prospect_id])),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this call?')
                    ->modalDescription(fn (Meeting $record): string => 'Both you and ' . $record->name
                        . ' will be emailed, and the time goes back on the calendar.')
                    ->visible(fn (Meeting $record): bool => ! $record->isCanceled() && ! $record->isPast())
                    ->action(function (Meeting $record) {
                        BookingService::cancel($record, by: 'host');

                        Notification::make()->success()->title('Call canceled')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMeetings::route('/')];
    }
}
