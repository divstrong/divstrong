<?php

namespace App\Filament\Resources\RfpScreenResource\Widgets;

use App\Filament\Resources\RfpScreenResource\Widgets\ScreenahDateRange;
use App\Models\RfpScreen;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class RfpScreenStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $base = fn () => $this->applyDateFilter(RfpScreen::forUser()->where('status', 'completed'));

        $great = (clone $base())->where('score', '>=', 75)->count();
        $good = (clone $base())->whereBetween('score', [50, 74])->count();
        $notUs = (clone $base())->where('score', '<', 50)->count();

        $filterUrl = fn (string $bucket) => \App\Filament\Resources\RfpScreenResource::getUrl('index', [
            'fit_score' => $bucket,
        ]);

        return [
            Stat::make('Great Fits', $great)
                ->description('Score 75+')
                ->icon('heroicon-o-star')
                ->color('success')
                ->url($filterUrl('great')),
            Stat::make('Good Fits', $good)
                ->description('Score 50–74')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('warning')
                ->url($filterUrl('good')),
            Stat::make('Not Us', $notUs)
                ->description('Score below 50')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->url($filterUrl('not_us')),
        ];
    }

    protected function applyDateFilter(Builder $query): Builder
    {
        $range = ScreenahDateRange::resolve($this->filters ?? []);

        if ($range === null) {
            return $query;
        }

        [$start, $end] = $range;

        return $query->whereBetween('created_at', [$start, $end]);
    }
}
