<?php

namespace App\Filament\Widgets;

use App\Models\Proposal;
use App\Models\ProposalPayment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ActualRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Actual Revenue';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    public function getDescription(): ?string
    {
        [$start, $end] = $this->getDateRange();

        return '$' . number_format($this->paymentsQuery($start, $end)->sum('amount')) . ' received';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        $payments = $this->paymentsQuery($start, $end)->get(['amount', 'paid_at']);

        $labels = [];
        $data = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $labels[] = $current->format('M Y');
            $data[] = round(
                $payments
                    ->filter(fn ($p) => $p->paid_at->between($monthStart, $monthEnd))
                    ->sum('amount'),
                2
            );

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Payments Received',
                    'data' => $data,
                    'backgroundColor' => '#16a34a',
                    'borderColor' => '#15803d',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function paymentsQuery(Carbon $start, Carbon $end)
    {
        return ProposalPayment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->whereIn('proposal_id', Proposal::forUser()->select('id'));
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
                Carbon::parse($this->filters['date_start'] ?? $now->copy()->startOfYear())->startOfDay(),
                Carbon::parse($this->filters['date_end'] ?? $now->copy()->endOfYear())->endOfDay(),
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
