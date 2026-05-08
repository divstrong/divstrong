<?php

namespace App\Actions;

use App\Filament\Resources\RfpScreenResource;
use App\Models\RfpScreen;
use App\Models\User;
use App\Services\ClaudeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ScreenRfp
{
    public function __construct(private ClaudeService $claude) {}

    public function create(User $user, UploadedFile $file, ?string $prompt = null, ?string $rfpName = null): RfpScreen
    {
        $storedPath = $file->store('rfp-documents', 'public');

        $screen = RfpScreen::create([
            'user_id' => $user->id,
            'filename' => basename($storedPath),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'rfp_name' => $rfpName,
            'prompt' => $prompt ?: RfpScreenResource::getDefaultPrompt(),
            'status' => 'analyzing',
        ]);

        $this->analyze($screen);

        return $screen->fresh();
    }

    public function rescan(RfpScreen $screen, ?UploadedFile $replacement = null): RfpScreen
    {
        if ($replacement) {
            $oldPath = $screen->file_path;
            $newPath = $replacement->store('rfp-documents', 'public');

            $screen->update([
                'file_path' => $newPath,
                'filename' => basename($newPath),
                'original_filename' => $replacement->getClientOriginalName(),
                'file_type' => strtolower($replacement->getClientOriginalExtension()),
            ]);

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $screen->update(['status' => 'analyzing']);

        $this->analyze($screen);

        return $screen->fresh();
    }

    private function analyze(RfpScreen $screen): void
    {
        try {
            $prompt = $screen->prompt;
            if (! str_contains((string) $prompt, 'contact_name')
                || ! str_contains((string) $prompt, 'COTS')
                || ! str_contains((string) $prompt, 'locality_city')
                || ! str_contains((string) $prompt, 'target_department')) {
                $prompt = RfpScreenResource::getDefaultPrompt();
            }

            $result = $this->claude->analyzeRfp(
                $screen->file_path,
                $prompt,
                $screen->attachments()->pluck('file_path')->all(),
            );

            $update = [
                'score' => $result['score'],
                'due_date' => $result['due_date'] ?? $screen->due_date,
                'contact_name' => $result['contact_name'] ?? $screen->contact_name,
                'contact_title' => $result['contact_title'] ?? $screen->contact_title,
                'contact_department' => $result['contact_department'] ?? $screen->contact_department,
                'contact_email' => $result['contact_email'] ?? $screen->contact_email,
                'contact_phone' => $result['contact_phone'] ?? $screen->contact_phone,
                'locality_city' => $result['locality_city'] ?? $screen->locality_city,
                'locality_state' => $result['locality_state'] ?? $screen->locality_state,
                'locality_county' => $result['locality_county'] ?? $screen->locality_county,
                'target_department' => $result['target_department'] ?? $screen->target_department,
                'summary' => $result['summary'],
                'red_flags' => $result['red_flags'],
                'requirements' => $result['requirements'],
                'raw_response' => $result['raw_response'],
                'status' => 'completed',
                'analyzed_at' => now(),
                'scanned_with_model' => $this->claude->getModel(),
            ];

            if (empty($screen->rfp_name) && ! empty($result['rfp_name'])) {
                $update['rfp_name'] = $result['rfp_name'];
            }

            $screen->update($update);
        } catch (\Throwable $e) {
            $screen->update([
                'status' => 'failed',
                'raw_response' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
