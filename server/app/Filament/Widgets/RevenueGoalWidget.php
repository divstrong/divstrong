<?php

namespace App\Filament\Widgets;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueGoalWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected string $view = 'filament.widgets.revenue-goal-widget';

    private const GOAL = 250000;

    public function getGoalData(): array
    {
        [$start, $end] = $this->getDateRange();

        $converted = Proposal::forUser()
            ->where('status', ProposalStatus::Converted)
            ->whereBetween('proposal_date', [$start, $end])
            ->with('costItems')
            ->get()
            ->sum(fn ($p) => $p->subtotal);

        $percentage = min(round(($converted / self::GOAL) * 100, 1), 100);

        return [
            'converted' => $converted,
            'goal' => self::GOAL,
            'percentage' => $percentage,
        ];
    }

    protected function getDateRange(): array
    {
        $preset = $this->filters['date_range'] ?? 'this_year';
        $now = Carbon::now();

        if ($preset === 'last_year') {
            return [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()];
        }

        if ($preset === 'this_month') {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }

        if ($preset === 'last_month') {
            return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
        }

        if ($preset === 'this_quarter') {
            return [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()];
        }

        if ($preset === 'last_quarter') {
            return [$now->copy()->subQuarter()->firstOfQuarter(), $now->copy()->subQuarter()->lastOfQuarter()];
        }

        if ($preset === 'all_time') {
            return [Carbon::create(2020, 1, 1), $now->copy()->endOfYear()];
        }

        if ($preset === 'custom') {
            return [
                Carbon::parse($this->filters['date_start'] ?? $now->copy()->startOfYear()),
                Carbon::parse($this->filters['date_end'] ?? $now->copy()->endOfYear()),
            ];
        }

        return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
    }
}
