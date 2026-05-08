<?php

namespace App\Livewire;

use App\Models\RfpScreen;
use App\Services\BudgetIntelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class BudgetIntelModal extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $screenId;

    public string $state = 'idle';

    public ?array $intel = null;
    public ?string $intelAt = null;
    public ?string $intelModel = null;
    public ?string $errorMessage = null;

    public ?string $localityCity = null;
    public ?string $localityState = null;
    public ?string $localityCounty = null;
    public ?string $targetDepartment = null;

    public $budgetFile = null;

    public function mount(): void
    {
        $screen = RfpScreen::findOrFail($this->screenId);

        $this->localityCity = $screen->locality_city;
        $this->localityState = $screen->locality_state;
        $this->localityCounty = $screen->locality_county;
        $this->targetDepartment = $screen->target_department;

        if (! empty($screen->budget_intel)) {
            $this->intel = $screen->budget_intel;
            $this->intelAt = optional($screen->budget_intel_at)->toDayDateTimeString();
            $this->intelModel = $screen->budget_intel_model;
            $this->state = 'complete';
        }
    }

    protected function rules(): array
    {
        return [
            'budgetFile' => 'nullable|file|max:30720|mimes:pdf,doc,docx,txt,csv,md',
        ];
    }

    public function runSearch(BudgetIntelService $service): void
    {
        $this->validate();

        $screen = RfpScreen::findOrFail($this->screenId);

        if (empty($screen->locality_city) && empty($screen->locality_state) && empty($screen->locality_county)) {
            $this->errorMessage = 'No locality on file. Rescan the RFP first so we know which municipality to research.';
            $this->state = 'failed';
            return;
        }

        try {
            $result = $service->search(
                $screen->locality_city,
                $screen->locality_state,
                $screen->locality_county,
                $screen->target_department,
            );

            $this->persistIntel($screen, $service, $result);
        } catch (\Throwable $e) {
            Log::error('Budget Intel web search failed', [
                'rfp_screen_id' => $this->screenId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = $e->getMessage();
            $this->state = 'failed';
        }
    }

    public function scanDocument(BudgetIntelService $service): void
    {
        $this->validate([
            'budgetFile' => 'required|file|max:30720|mimes:pdf,doc,docx,txt,csv,md',
        ]);

        $screen = RfpScreen::findOrFail($this->screenId);

        $storedPath = null;

        try {
            $storedPath = $this->budgetFile->store('budget-documents', 'public');

            $result = $service->analyzeFromDocument(
                $storedPath,
                $screen->locality_city,
                $screen->locality_state,
                $screen->locality_county,
                $screen->target_department,
            );

            // Keep the uploaded doc so it can be linked from the Budget section as the source.
            $result['source_method'] = 'document';
            $result['source_filename'] = $this->budgetFile->getClientOriginalName();
            $result['source_file_path'] = $storedPath;

            $this->persistIntel($screen, $service, $result);
        } catch (\Throwable $e) {
            // If the analysis blew up, don't leave the orphan file behind.
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            Log::error('Budget Intel document scan failed', [
                'rfp_screen_id' => $this->screenId,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = $e->getMessage();
            $this->state = 'failed';
        }
    }

    private function persistIntel(RfpScreen $screen, BudgetIntelService $service, array $result): void
    {
        // If a previous run had an uploaded source doc and we're replacing it with a different
        // file (or with a web-search result that has no file), clean up the old one.
        $previousPath = $screen->budget_intel['source_file_path'] ?? null;
        $newPath = $result['source_file_path'] ?? null;
        if ($previousPath && $previousPath !== $newPath) {
            Storage::disk('public')->delete($previousPath);
        }

        $screen->update([
            'budget_intel' => $result,
            'budget_intel_at' => now(),
            'budget_intel_model' => $service->getModel(),
        ]);

        $this->intel = $result;
        $this->intelAt = $screen->fresh()->budget_intel_at?->toDayDateTimeString();
        $this->intelModel = $service->getModel();
        $this->errorMessage = null;
        $this->budgetFile = null;
        $this->state = 'complete';
    }

    public function rerun(): void
    {
        $this->state = 'idle';
        $this->errorMessage = null;
        $this->budgetFile = null;
    }

    public function render()
    {
        return view('livewire.budget-intel-modal');
    }
}
