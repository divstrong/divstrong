<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Filament\Resources\RfpScreenResource;
use App\Filament\Resources\RfpScreenResource\Widgets\RfpScreenStatsWidget;
use App\Filament\Resources\RfpScreenResource\Widgets\ScreenahDateRange;
use App\Mail\RfpAnalysisComplete;
use App\Models\RfpScreen;
use App\Services\ClaudeService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ListRfpScreens extends ListRecords
{
    protected static string $resource = RfpScreenResource::class;

    public ?array $filters = [
        'date_range' => 'all_time',
        'date_start' => null,
        'date_end' => null,
    ];

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
            \Filament\Schemas\Components\EmbeddedSchema::make('filtersForm'),
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

    protected function getHeaderWidgets(): array
    {
        return [
            RfpScreenStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Screen New RFP')
                ->modalHeading('Screen RFP')
                ->createAnother(false)
                ->modalSubmitActionLabel('Screen')
                ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    $filePath = $data['file_path'];
                    $data['original_filename'] = basename($filePath);
                    $data['filename'] = basename($filePath);
                    $data['file_type'] = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $data['status'] = 'analyzing';

                    return $data;
                })
                ->after(function (RfpScreen $record) {
                    try {
                        $service = new ClaudeService();
                        $result = $service->analyzeRfp($record->file_path, $record->prompt);

                        $updateData = [
                            'score' => $result['score'],
                            'due_date' => $result['due_date'] ?? null,
                            'summary' => $result['summary'],
                            'red_flags' => $result['red_flags'],
                            'requirements' => $result['requirements'],
                            'raw_response' => $result['raw_response'],
                            'status' => 'completed',
                            'analyzed_at' => now(),
                        ];

                        if (empty($record->rfp_name) && !empty($result['rfp_name'])) {
                            $updateData['rfp_name'] = $result['rfp_name'];
                        }

                        $record->update($updateData);

                        $label = $record->score_label;
                        Notification::make()
                            ->title("RFP Analysis Complete — {$record->score}/100 ({$label})")
                            ->success()
                            ->send();

                        Mail::to('jim@divstrong.com')->queue(new RfpAnalysisComplete($record));
                    } catch (\Throwable $e) {
                        Log::error('RFP screening failed', [
                            'rfp_screen_id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);

                        $record->update([
                            'status' => 'failed',
                            'raw_response' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('RFP Analysis Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
