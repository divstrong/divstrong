<?php

namespace App\Filament\Widgets;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProposalStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$start, $end] = $this->getDateRange();

        $proposals = Proposal::forUser()
            ->whereBetween('proposal_date', [$start, $end])
            ->with('costItems')
            ->get();

        $pending = $proposals->whereIn('status', [ProposalStatus::Sent, ProposalStatus::Viewed]);
        $accepted = $proposals->whereIn('status', [ProposalStatus::Accepted, ProposalStatus::Converted]);
        $declined = $proposals->where('status', ProposalStatus::Declined);

        $closedDecisions = $pending->count() + $accepted->count() + $declined->count();
        $rate = $closedDecisions > 0
            ? round(($accepted->count() / $closedDecisions) * 100, 1)
            : 0;

        $acceptedValue = $accepted->sum(fn ($p) => $p->subtotal);

        return [
            Stat::make('Pending', $pending->count())
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Accepted', $accepted->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Acceptance Rate', $rate . '%')
                ->icon('heroicon-o-calculator')
                ->color('warning'),
            Stat::make('Accepted Value', '$' . number_format($acceptedValue))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }

    protected function getDateRange(): array
    {
        $preset = $this->filters['date_range'] ?? 'this_year';
        $now = Carbon::now();

        return match ($preset) {
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()],
            'last_quarter' => [$now->copy()->subQuarter()->firstOfQuarter(), $now->copy()->subQuarter()->lastOfQuarter()],
            'all_time' => [Carbon::create(2020, 1, 1), $now->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($this->filters['date_start'] ?? $now->copy()->startOfYear()),
                Carbon::parse($this->filters['date_end'] ?? $now->copy()->endOfYear()),
            ],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }
}
