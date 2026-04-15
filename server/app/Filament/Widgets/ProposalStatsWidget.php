<?php

namespace App\Filament\Widgets;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProposalStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Draft', Proposal::forUser()->where('status', ProposalStatus::Draft)->count())
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
            Stat::make('Sent', Proposal::forUser()->where('status', ProposalStatus::Sent)->count())
                ->icon('heroicon-o-paper-airplane')
                ->color('info'),
            Stat::make('Accepted', Proposal::forUser()->where('status', ProposalStatus::Accepted)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Total Accepted Value',
                '$' . number_format(
                    Proposal::forUser()->where('status', ProposalStatus::Accepted)
                        ->with('costItems')
                        ->get()
                        ->sum(fn ($p) => $p->subtotal),
                    2
                )
            )
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
