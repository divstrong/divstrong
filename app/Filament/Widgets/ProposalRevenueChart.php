<?php

namespace App\Filament\Widgets;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ProposalRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Proposal Revenue';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        $months = [];
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        $pendingStatuses = [
            ProposalStatus::Draft,
            ProposalStatus::Sent,
            ProposalStatus::Viewed,
            ProposalStatus::Accepted,
        ];

        // Load all proposals in range with cost items in one query
        $proposals = Proposal::whereBetween('proposal_date', [$start, $end])
            ->with('costItems')
            ->get();

        $pendingData = [];
        $convertedData = [];
        $labels = [];

        foreach ($months as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $labels[] = $month->format('M Y');

            $monthProposals = $proposals->filter(
                fn ($p) => $p->proposal_date->between($monthStart, $monthEnd)
            );

            $pendingData[] = round(
                $monthProposals
                    ->filter(fn ($p) => in_array($p->status, $pendingStatuses))
                    ->sum(fn ($p) => $p->subtotal),
                2
            );

            $convertedData[] = round(
                $monthProposals
                    ->filter(fn ($p) => $p->status === ProposalStatus::Converted)
                    ->sum(fn ($p) => $p->subtotal),
                2
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pending',
                    'data' => $pendingData,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Converted',
                    'data' => $convertedData,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
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
            'custom' => [
                Carbon::parse($this->filters['date_start'] ?? $now->copy()->startOfYear()),
                Carbon::parse($this->filters['date_end'] ?? $now->copy()->endOfYear()),
            ],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
