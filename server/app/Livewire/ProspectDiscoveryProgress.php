<?php

namespace App\Livewire;

use App\Models\ProspectDiscoveryRun as Run;
use App\Support\Prospecting\DiscoveryRunner;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The progress banner above the Prospects table, and the thing that actually drives a
 * discovery run forward.
 *
 * There is no queue worker on these installs, so the work has to be pulled by something. This
 * component is that something: each poll asks the runner for one bounded unit of work and
 * repaints. Closing the tab pauses a run rather than breaking it — the run row holds the state,
 * and reopening the page picks it back up.
 *
 * It renders nothing at all when there is no run to talk about, so the page is unchanged for
 * anyone not using the feature.
 */
class ProspectDiscoveryProgress extends Component
{
    public ?int $runId = null;

    /** Set once a run ends, so the finished banner stays visible until dismissed. */
    public bool $dismissed = false;

    public function mount(): void
    {
        // Resume whatever this user last started, so a reloaded page is not a lost run.
        $run = Run::query()
            ->where('requested_by', auth()->id())
            ->whereIn('status', Run::ACTIVE_STATUSES)
            ->latest('id')
            ->first();

        $this->runId = $run?->id;
    }

    #[On('discovery-started')]
    public function attach(int $runId): void
    {
        $this->runId = $runId;
        $this->dismissed = false;
    }

    public function getRunProperty(): ?Run
    {
        return $this->runId ? Run::find($this->runId) : null;
    }

    /**
     * Advance the run by one step. Called by wire:poll while a run is live.
     */
    public function poll(): void
    {
        $run = $this->run;

        if (! $run || $run->isFinished()) {
            return;
        }

        // A discovery round is one web-search-backed API call and can run past the default
        // execution limit. The poll is a normal web request, so raise the ceiling for it.
        @set_time_limit(300);

        $before = $run->status;

        // forRun, not make: the run chose its own source when it was started, and the
        // server default may well be the other one.
        $run = DiscoveryRunner::forRun($run)->advance($run);

        if ($run->isFinished()) {
            $this->announce($run);

            // New rows: tell the table to re-read itself rather than waiting for a click.
            $this->dispatch('$refresh')
                ->to(\App\Filament\Resources\ProspectResource\Pages\ListProspects::class);
        } elseif ($before !== $run->status) {
            $this->dispatch('$refresh')
                ->to(\App\Filament\Resources\ProspectResource\Pages\ListProspects::class);
        }
    }

    public function cancel(): void
    {
        $run = $this->run;

        if ($run && $run->isActive()) {
            DiscoveryRunner::make()->cancel($run);

            Notification::make()
                ->warning()
                ->title('Discovery cancelled')
                ->body("{$run->imported} prospects were added before it stopped.")
                ->send();
        }
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
        $this->runId = null;
    }

    /**
     * Tell the operator what came back, including what did not.
     *
     * The breakdown is the useful half. "50 searched, 12 added" reads like a broken feature
     * until you can see that 20 agencies publish no named address at all — which is a fact
     * about the industry, not a bug.
     */
    private function announce(Run $run): void
    {
        if ($run->status === Run::STATUS_FAILED) {
            Notification::make()
                ->danger()
                ->title('Discovery failed')
                ->body($run->error ?? 'Unknown error')
                ->persistent()
                ->send();

            return;
        }

        if ($run->status === Run::STATUS_CANCELLED) {
            return;
        }

        $breakdown = DiscoveryRunner::rejectionBreakdown($run);

        $detail = collect($breakdown)
            ->sortDesc()
            ->map(fn (int $n, string $reason) => $n.' '.DiscoveryRunner::reasonLabel($reason))
            ->take(4)
            ->implode(', ');

        $body = "{$run->companies_found} agencies researched, {$run->imported} added with a verified named address.";

        if ($detail !== '') {
            $body .= ' Skipped: '.$detail.'.';
        }

        Notification::make()
            ->{$run->imported > 0 ? 'success' : 'warning'}()
            ->title($run->imported > 0 ? "Added {$run->imported} prospects" : 'No new prospects found')
            ->body($body)
            ->persistent()
            ->send();
    }

    public function render()
    {
        return view('livewire.prospect-discovery-progress', [
            'run' => $this->dismissed ? null : $this->run,
        ]);
    }
}
