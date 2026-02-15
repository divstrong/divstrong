<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProposalRevenueChart;
use App\Filament\Widgets\ProposalStatsWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('date_range')
                    ->options([
                        'this_year' => 'This Year',
                        'last_year' => 'Last Year',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_quarter' => 'This Quarter',
                        'last_quarter' => 'Last Quarter',
                        'custom' => 'Custom',
                    ])
                    ->default('this_year')
                    ->live(),
                DatePicker::make('date_start')
                    ->label('Start Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom'),
                DatePicker::make('date_end')
                    ->label('End Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom'),
            ]);
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            ProposalStatsWidget::class,
            ProposalRevenueChart::class,
        ];
    }
}
