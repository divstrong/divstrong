{{--
    Discovery progress banner for the Prospects list.

    Renders an empty div when there is no run, so the page looks untouched for anyone not using
    the feature — and so Livewire still has a root element to poll against.

    wire:poll only exists while a run is live. A finished banner stops polling and waits to be
    dismissed, which is why the poll attribute is conditional rather than always-on.
--}}
<div>
    @if ($run)
        <div
            @if ($run->isActive()) wire:poll.4s="poll" @endif
            class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if ($run->isActive())
                        <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                    @elseif ($run->status === \App\Models\ProspectDiscoveryRun::STATUS_COMPLETE)
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500" />
                    @else
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-danger-500" />
                    @endif

                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            @if ($run->isActive())
                                Finding prospects
                            @elseif ($run->status === \App\Models\ProspectDiscoveryRun::STATUS_COMPLETE)
                                Discovery complete
                            @elseif ($run->status === \App\Models\ProspectDiscoveryRun::STATUS_CANCELLED)
                                Discovery cancelled
                            @else
                                Discovery failed
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $run->error ?: $run->stage_message }}
                            {{-- Which source this run chose. The two cost wildly different
                                 amounts, so it should never be a guess which one is running. --}}
                            @if ($via = ($run->criteria[\App\Support\Prospecting\DiscoveryRunner::CRITERIA_KEY] ?? null))
                                <span class="text-gray-400 dark:text-gray-500">
                                    · via {{ \App\Support\Prospecting\DiscoveryRunner::source($via)->name() }}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Counters, not just a bar: "how many are actually usable" is the number
                         being waited on, and a bar cannot say it. --}}
                    <div class="flex items-center gap-4 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $run->companies_found }}</span>
                            agencies
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-success-600 dark:text-success-400">{{ $run->accepted }}</span>
                            verified
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $run->imported }}</span>
                            added
                        </span>
                        <span class="text-gray-400 dark:text-gray-500">
                            target {{ $run->target_count }}
                        </span>
                        {{-- Search cost is invisible otherwise, and it is the one number that
                             surprises people: web search re-bills its whole context on every
                             step, so a run can spend six figures of tokens quietly. --}}
                        @if ($run->tokens_in > 0)
                            <span class="text-gray-400 dark:text-gray-500" title="Input tokens used by web search so far">
                                {{ number_format($run->tokens_in / 1000) }}k tokens
                            </span>
                        @endif
                    </div>

                    @if ($run->isActive())
                        <x-filament::button size="xs" color="gray" wire:click="cancel">
                            Stop
                        </x-filament::button>
                    @else
                        <x-filament::button size="xs" color="gray" wire:click="dismiss">
                            Dismiss
                        </x-filament::button>
                    @endif
                </div>
            </div>

            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                {{-- Inline width: Tailwind cannot emit a class for a number computed at runtime. --}}
                <div
                    class="h-full rounded-full transition-all duration-500 {{ $run->status === \App\Models\ProspectDiscoveryRun::STATUS_FAILED ? 'bg-danger-500' : 'bg-primary-500' }}"
                    style="width: {{ $run->progress() }}%"
                ></div>
            </div>
        </div>
    @endif
</div>
