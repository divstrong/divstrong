<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only access to the daily log files in storage/logs.
 *
 * Nothing here takes a path from the caller. A file is addressed by its bare name and
 * only ever resolved against the list this class built itself, so a crafted name can't
 * walk out of the log directory.
 */
class ServerLogFiles
{
    /** Counting entries means reading the file, so large ones are left uncounted. */
    private const COUNTABLE_BYTES = 8 * 1024 * 1024;

    /** Matches the opening of a log entry: [2026-09-22 17:03:02] local.ERROR: … */
    private const ENTRY = '/^\[(\d{4}-\d{2}-\d{2}[ T][\d:.]+)[^\]]*\]\s+(\S+?)\.([A-Z]+):/m';

    public static function directory(): string
    {
        return storage_path('logs');
    }

    /**
     * Every readable log file, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function all(): Collection
    {
        $files = glob(self::directory() . DIRECTORY_SEPARATOR . '*.log') ?: [];

        return collect($files)
            ->filter(fn (string $path) => is_file($path) && is_readable($path))
            ->map(fn (string $path) => self::describe($path))
            ->sortByDesc(fn (array $file) => [$file['date']?->timestamp ?? 0, $file['modified_at']->timestamp])
            ->values();
    }

    /** @return array<string, mixed>|null */
    public static function find(string $name): ?array
    {
        // The name itself is accepted too, so an old link with ".log" still resolves.
        return self::all()->first(fn (array $file) => $file['id'] === $name || $file['name'] === $name);
    }

    /**
     * The last $limit lines of a file, read from the end so a 200 MB log costs the same
     * as a small one. Returns them in file order.
     *
     * @return array{lines: array<int, string>, truncated: bool}
     */
    public static function tail(string $path, int $limit): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return ['lines' => [], 'truncated' => false];
        }

        $buffer = '';
        $chunk = 8192;
        $position = filesize($path);
        $newlines = 0;

        while ($position > 0 && $newlines <= $limit) {
            $read = (int) min($chunk, $position);
            $position -= $read;

            fseek($handle, $position);
            $slice = fread($handle, $read);

            $buffer = $slice . $buffer;
            $newlines = substr_count($buffer, "\n");
        }

        fclose($handle);

        $lines = preg_split('/\r\n|\r|\n/', rtrim($buffer, "\r\n")) ?: [];
        $truncated = count($lines) > $limit || $position > 0;

        return [
            'lines' => array_slice($lines, -$limit),
            'truncated' => $truncated,
        ];
    }

    /** The level of a line, or null when it is a continuation such as a stack frame. */
    public static function levelOf(string $line): ?string
    {
        if (preg_match('/^\[[^\]]+\]\s+\S+?\.([A-Z]+):/', $line, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private static function describe(string $path): array
    {
        $name = basename($path);
        $size = filesize($path) ?: 0;
        $counts = $size <= self::COUNTABLE_BYTES ? self::count($path) : null;

        return [
            // The URL key, without ".log": Plesk's nginx denies any request path ending in
            // .log (to stop log files being downloaded), so a viewer URL ending in the file
            // name was refused with a 403 before it ever reached Laravel.
            'id' => preg_replace('/\.log$/i', '', $name),
            'name' => $name,
            'path' => $path,
            'date' => self::dateFrom($name),
            'label' => self::labelFor($name),
            'size' => $size,
            'modified_at' => Carbon::createFromTimestamp(filemtime($path) ?: 0),
            'entries' => $counts['entries'] ?? null,
            'errors' => $counts['errors'] ?? null,
        ];
    }

    /** @return array{entries: int, errors: int} */
    private static function count(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return ['entries' => 0, 'errors' => 0];
        }

        preg_match_all(self::ENTRY, $contents, $matches);

        $levels = collect($matches[3] ?? []);

        return [
            'entries' => $levels->count(),
            // Anything at or above ERROR is what someone opening this page is looking for.
            'errors' => $levels->filter(fn (string $level) => in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true))->count(),
        ];
    }

    private static function dateFrom(string $name): ?Carbon
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $name, $matches) === 1) {
            return Carbon::parse($matches[1])->startOfDay();
        }

        return null;
    }

    private static function labelFor(string $name): string
    {
        $date = self::dateFrom($name);

        if (! $date) {
            return Str::of($name)->beforeLast('.log')->headline()->toString();
        }

        return match (true) {
            $date->isToday() => 'Today · ' . $date->format('M j, Y'),
            $date->isYesterday() => 'Yesterday · ' . $date->format('M j, Y'),
            default => $date->format('l, M j, Y'),
        };
    }
}
