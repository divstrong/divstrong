<?php

namespace App\Livewire;

use App\Actions\ScreenRfp;
use App\Filament\Resources\RfpScreenResource;
use App\Models\RfpScreen;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class RfpRescanModal extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $screenId;

    public string $state = 'form';

    public $replacementFile = null;

    public ?int $score = null;
    public ?string $scoreLabel = null;
    public ?string $modelLabel = null;
    public ?string $viewUrl = null;
    public ?string $errorMessage = null;

    protected function rules(): array
    {
        return [
            'replacementFile' => 'nullable|file|max:20480|mimes:pdf,doc,docx,txt,csv,md',
        ];
    }

    public function rescan(ScreenRfp $screenAction): void
    {
        $this->validate();

        $screen = RfpScreen::findOrFail($this->screenId);

        try {
            $screenAction->rescan($screen, $this->replacementFile);
            $screen->refresh();

            $this->score = $screen->score;
            $this->scoreLabel = $screen->score_label;
            $this->modelLabel = $screen->scanned_with_model_label;
            $this->viewUrl = RfpScreenResource::getUrl('view', ['record' => $screen]);
            $this->state = 'complete';
        } catch (\Throwable $e) {
            Log::error('RFP rescan failed', [
                'rfp_screen_id' => $this->screenId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = $e->getMessage();
            $this->state = 'failed';
        }
    }

    public function reset_(): void
    {
        $this->reset(['state', 'replacementFile', 'score', 'scoreLabel', 'modelLabel', 'viewUrl', 'errorMessage']);
        $this->state = 'form';
    }

    public function render()
    {
        return view('livewire.rfp-rescan-modal');
    }
}
