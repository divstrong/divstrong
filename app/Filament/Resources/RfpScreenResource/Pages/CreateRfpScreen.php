<?php

namespace App\Filament\Resources\RfpScreenResource\Pages;

use App\Filament\Resources\RfpScreenResource;
use App\Services\ClaudeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateRfpScreen extends CreateRecord
{
    protected static string $resource = RfpScreenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Extract file metadata from the uploaded file path
        $filePath = $data['file_path'];
        $data['original_filename'] = basename($filePath);
        $data['filename'] = basename($filePath);
        $data['file_type'] = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $data['status'] = 'analyzing';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        try {
            $service = new ClaudeService();
            $result = $service->analyzeRfp($record->file_path, $record->prompt);

            $record->update([
                'rfp_name' => $result['rfp_name'],
                'score' => $result['score'],
                'summary' => $result['summary'],
                'red_flags' => $result['red_flags'],
                'requirements' => $result['requirements'],
                'raw_response' => $result['raw_response'],
                'status' => 'completed',
                'analyzed_at' => now(),
            ]);

            $label = $record->score_label;
            Notification::make()
                ->title("RFP Analysis Complete — {$record->score}/100 ({$label})")
                ->success()
                ->send();
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
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
