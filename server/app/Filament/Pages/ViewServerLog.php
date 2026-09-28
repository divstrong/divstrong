<?php

namespace App\Filament\Pages;

use App\Support\ServerLogFiles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ViewServerLog extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('server_logs') ?? false;
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $slug = 'server-logs/view';

    protected string $view = 'filament.pages.view-server-log';

    /** The file's bare name; only ever resolved against the files on disk. */
    public string $file = '';

    public int $lines = 500;

    public string $level = 'all';

    public string $search = '';

    /** @var array<string, mixed>|null */
    protected ?array $logFile = null;

    public static function getRoutePath(Panel $panel): string
    {
        return '/server-logs/view/{file}';
    }

    public function mount(string $file): void
    {
        $this->file = $file;

        // Resolving through the listing means an unknown or crafted name simply 404s.
        if (! ServerLogFiles::find($file)) {
            throw new NotFoundHttpException();
        }
    }

    public function getTitle(): string
    {
        return $this->logFile()['label'] ?? $this->file;
    }

    public function getSubheading(): ?string
    {
        return $this->file;
    }

    /** @return array<string, mixed> */
    public function logFile(): array
    {
        return $this->logFile ??= ServerLogFiles::find($this->file) ?? throw new NotFoundHttpException();
    }

    /**
     * The tail, grouped into entries so a stack trace stays attached to the line that
     * produced it — filtering line by line would scatter them.
     *
     * @return array{entries: array<int, array{level: ?string, time: ?string, text: string}>, truncated: bool, scanned: int}
     */
    public function entries(): array
    {
        $tail = ServerLogFiles::tail($this->logFile()['path'], $this->lines);

        $entries = [];

        foreach ($tail['lines'] as $line) {
            $level = ServerLogFiles::levelOf($line);

            if ($level === null && $entries !== []) {
                $entries[count($entries) - 1]['text'] .= "\n" . $line;

                continue;
            }

            $entries[] = [
                'level' => $level,
                'time' => preg_match('/^\[([^\]]+)\]/', $line, $m) === 1 ? $m[1] : null,
                'text' => $line,
            ];
        }

        $filtered = collect($entries)
            ->when($this->level !== 'all', fn ($rows) => $rows->filter(
                fn (array $entry) => in_array($entry['level'], $this->levelGroup(), true)
            ))
            ->when(filled($this->search), fn ($rows) => $rows->filter(
                fn (array $entry) => str_contains(strtolower($entry['text']), strtolower(trim($this->search)))
            ))
            ->values()
            ->all();

        return [
            'entries' => $filtered,
            'truncated' => $tail['truncated'],
            'scanned' => count($entries),
        ];
    }

    /** @return array<int, string> */
    protected function levelGroup(): array
    {
        return match ($this->level) {
            'error' => ['error', 'critical', 'alert', 'emergency'],
            'warning' => ['warning'],
            'info' => ['info', 'notice'],
            'debug' => ['debug'],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function levelOptions(): array
    {
        return [
            'all' => 'All levels',
            'error' => 'Errors',
            'warning' => 'Warnings',
            'info' => 'Info',
            'debug' => 'Debug',
        ];
    }

    /** @return array<int, string> */
    public function lineOptions(): array
    {
        return [200 => '200 lines', 500 => '500 lines', 2000 => '2,000 lines', 10000 => '10,000 lines'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => response()->download($this->logFile()['path'])),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => null),
            Action::make('back')
                ->label('All logs')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->url(ServerLogs::getUrl()),
        ];
    }
}
