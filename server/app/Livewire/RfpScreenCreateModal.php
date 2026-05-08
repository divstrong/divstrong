<?php

namespace App\Livewire;

use App\Actions\ScreenRfp;
use App\Filament\Resources\RfpScreenResource;
use App\Mail\RfpAnalysisComplete;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithFileUploads;

class RfpScreenCreateModal extends Component
{
    use WithFileUploads;

    public string $state = 'form';

    public $file = null;
    public string $prompt = '';

    public ?int $score = null;
    public ?string $scoreLabel = null;
    public ?string $modelLabel = null;
    public ?string $viewUrl = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->prompt = RfpScreenResource::getDefaultPrompt();
    }

    protected function rules(): array
    {
        return [
            'file' => 'required|file|max:20480|mimes:pdf,doc,docx,txt,csv,md',
            'prompt' => 'required|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'file.required' => 'Please upload an RFP document to screen.',
        ];
    }

    public function screen(ScreenRfp $screenAction): void
    {
        $this->validate();

        try {
            $screen = $screenAction->create(
                Auth::user(),
                $this->file,
                $this->prompt,
                null,
            );

            try {
                Mail::to('jim@divstrong.com')->queue(new RfpAnalysisComplete($screen));
            } catch (\Throwable $mailError) {
                Log::warning('RFP analysis email queue failed', [
                    'rfp_screen_id' => $screen->id,
                    'error' => $mailError->getMessage(),
                ]);
            }

            $this->score = $screen->score;
            $this->scoreLabel = $screen->score_label;
            $this->modelLabel = $screen->scanned_with_model_label;
            $this->viewUrl = RfpScreenResource::getUrl('view', ['record' => $screen]);
            $this->state = 'complete';
        } catch (\Throwable $e) {
            Log::error('RFP create+screen failed', [
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = $e->getMessage();
            $this->state = 'failed';
        }
    }

    public function reset_(): void
    {
        $this->reset(['state', 'file', 'score', 'scoreLabel', 'modelLabel', 'viewUrl', 'errorMessage']);
        $this->prompt = RfpScreenResource::getDefaultPrompt();
        $this->state = 'form';
    }

    public function render()
    {
        return view('livewire.rfp-screen-create-modal');
    }
}
