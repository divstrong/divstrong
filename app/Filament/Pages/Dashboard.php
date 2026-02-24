<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ProposalResource;
use App\Filament\Widgets\ProposalRevenueChart;
use App\Filament\Widgets\ProposalStatsWidget;
use App\Filament\Widgets\RevenueGoalWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('dashboard') ?? false;
    }

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('dashboard')) {
            $redirectMap = [
                'clients' => ClientResource::getUrl(),
                'proposals' => ProposalResource::getUrl(),
            ];

            foreach ($redirectMap as $permission => $url) {
                if (auth()->user()->hasPermission($permission)) {
                    $this->redirect($url);

                    return;
                }
            }
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('date_range')
                    ->options([
                        'this_year' => 'This Year',
                        'last_year' => 'Last Year',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_quarter' => 'This Quarter',
                        'last_quarter' => 'Last Quarter',
                        'all_time' => 'All Time',
                        'custom' => 'Custom',
                    ])
                    ->default('this_year')
                    ->columnSpanFull()
                    ->live(),
                DatePicker::make('date_start')
                    ->label('Start Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom'),
                DatePicker::make('date_end')
                    ->label('End Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom'),
            ]);
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema(fn (): array => [
                        ...$this->getWidgetsSchemaComponents([ProposalStatsWidget::class]),
                        ...$this->getWidgetsSchemaComponents([ProposalRevenueChart::class]),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                EmbeddedSchema::make('filtersForm'),
                                ...$this->getWidgetsSchemaComponents([RevenueGoalWidget::class]),
                            ]),
                    ]),
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
            RevenueGoalWidget::class,
        ];
    }
}
