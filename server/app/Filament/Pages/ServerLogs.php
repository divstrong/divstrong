<?php

namespace App\Filament\Pages;

use App\Support\ServerLogFiles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use UnitEnum;

class ServerLogs extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('server_logs') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'Server Logs';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 98;

    protected static ?string $title = 'Server Logs';

    protected string $view = 'filament.pages.server-logs';

    public function table(Table $table): Table
    {
        return $table
            // No model behind this table — the rows are files on disk, already newest first.
            ->records(fn (): \Illuminate\Support\Collection => ServerLogFiles::all())
            ->columns([
                TextColumn::make('label')
                    ->label('Date')
                    ->weight('semibold')
                    ->description(fn (array $record): string => $record['name']),
                TextColumn::make('entries')
                    ->label('Entries')
                    ->placeholder('—')
                    ->alignRight(),
                TextColumn::make('errors')
                    ->label('Errors')
                    ->placeholder('—')
                    ->alignRight()
                    ->badge()
                    ->color(fn (?int $state): string => $state ? 'danger' : 'gray'),
                TextColumn::make('size')
                    ->label('Size')
                    ->alignRight()
                    ->formatStateUsing(fn (int $state): string => Number::fileSize($state, precision: 1)),
                TextColumn::make('modified_at')
                    ->label('Last write')
                    ->since()
                    ->tooltip(fn (array $record): string => $record['modified_at']->format('M j, Y g:i:s A')),
            ])
            ->recordUrl(fn (array $record): string => ViewServerLog::getUrl(['file' => $record['id']]))
            ->recordAction(null)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No log files')
            ->emptyStateDescription('Nothing has been written to storage/logs yet.')
            ->emptyStateIcon('heroicon-o-command-line');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => null),
        ];
    }
}
