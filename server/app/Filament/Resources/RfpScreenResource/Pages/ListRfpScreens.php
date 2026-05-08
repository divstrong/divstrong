<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Filament\Resources\RfpScreenResource;
use App\Filament\Resources\RfpScreenResource\Widgets\RfpScreenStatsWidget;
use App\Filament\Resources\RfpScreenResource\Widgets\ScreenahDateRange;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListRfpScreens extends ListRecords
{
    protected static string $resource = RfpScreenResource::class;

    public ?array $filters = [
        'date_range' => 'all_time',
        'date_start' => null,
        'date_end' => null,
    ];

    public function mount(): void
    {
        parent::mount();

        $fitScore = request()->query('fit_score');
        if (in_array($fitScore, ['great', 'good', 'not_us', 'pending'], true)) {
            $this->tableFilters = array_merge($this->tableFilters ?? [], [
                'fit_score' => ['value' => $fitScore],
            ]);
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('filters')
            ->columns(3)
            ->components([
                Select::make('date_range')
                    ->label('Date Range')
                    ->options([
                        'all_time' => 'All Time',
                        'this_month' => 'This Month',
                        'this_quarter' => 'This Quarter',
                        'this_year' => 'This Year',
                        'last_month' => 'Last Month',
                        'last_quarter' => 'Last Quarter',
                        'last_year' => 'Last Year',
                        'custom' => 'Custom',
                    ])
                    ->default('all_time')
                    ->selectablePlaceholder(false)
                    ->live(),
                DatePicker::make('date_start')
                    ->label('Start Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom')
                    ->live(),
                DatePicker::make('date_end')
                    ->label('End Date')
                    ->visible(fn (Get $get) => $get('date_range') === 'custom')
                    ->live(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Livewire::make(RfpScreenStatsWidget::class, fn () => ['pageFilters' => $this->filters]),
            $this->getTabsContentComponent(),
            \Filament\Schemas\Components\RenderHook::make(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            \Filament\Schemas\Components\EmbeddedTable::make(),
            \Filament\Schemas\Components\RenderHook::make(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    protected function getTableQuery(): Builder | Relation | null
    {
        $query = parent::getTableQuery() ?? static::getResource()::getEloquentQuery();

        $range = ScreenahDateRange::resolve($this->filters ?? []);

        if ($range !== null) {
            [$start, $end] = $range;
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('screenNewRfp')
                ->label('Screen RFP')
                ->icon('heroicon-o-plus')
                ->modalHeading('Screen RFP')
                ->modalContent(fn () => view('filament.rfp-screen-create-modal-host'))
                ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }
}
