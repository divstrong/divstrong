<?php

namespace App\Filament\Resources\RfpScreenResource\Widgets;

use Carbon\Carbon;

class ScreenahDateRange
{
    public static function resolve(array $filters): ?array
    {
        $preset = $filters['date_range'] ?? 'all_time';
        $now = Carbon::now();

        return match ($preset) {
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()],
            'last_quarter' => [$now->copy()->subQuarter()->firstOfQuarter(), $now->copy()->subQuarter()->lastOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'custom' => self::custom($filters),
            default => null,
        };
    }

    private static function custom(array $filters): ?array
    {
        $start = $filters['date_start'] ?? null;
        $end = $filters['date_end'] ?? null;

        if (! $start && ! $end) {
            return null;
        }

        return [
            $start ? Carbon::parse($start)->startOfDay() : Carbon::create(2000, 1, 1),
            $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay(),
        ];
    }
}
