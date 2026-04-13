<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Filament\Resources\RfpScreenResource;
use App\Mail\RfpAnalysisComplete;
use App\Models\RfpScreen;
use App\Services\ClaudeService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ListRfpScreens extends ListRecords
{
    protected static string $resource = RfpScreenResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\RfpScreenResource\Widgets\RfpScreenStatsWidget::class,
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
