<?php

namespace App\Filament\Resources\RfpScreenResource\Widgets;

use App\Models\RfpScreen;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class RfpScreenStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
            'all' => 'All Time',
        ];
    }

    protected function getStats(): array
    {
        $base = fn () => $this->applyDateFilter(RfpScreen::forUser()->where('status', 'completed'));

        $great = (clone $base())->where('score', '>=', 75)->count();
        $good = (clone $base())->whereBetween('score', [50, 74])->count();
        $notUs = (clone $base())->where('score', '<', 50)->count();

        return [
            Stat::make('Great Fits', $great)
                ->description('Score 75+')
                ->icon('heroicon-o-star')
                ->color('success'),
            Stat::make('Good Fits', $good)
                ->description('Score 50–74')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('warning'),
            Stat::make('Not Us', $notUs)
                ->description('Score below 50')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }

    protected function applyDateFilter(Builder $query): Builder
    {
        $now = now();

        return match ($this->filter) {
            'week' => $query->whereBetween('analyzed_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'month' => $query->whereBetween('analyzed_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            'quarter' => $query->whereBetween('analyzed_at', [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()]),
            'year' => $query->whereBetween('analyzed_at', [$now->copy()->startOfYear(), $now->copy()->endOfYear()]),
            default => $query,
        };
    }
}
