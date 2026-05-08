@php
    $localityParts = array_filter([
        $localityCity,
        $localityCounty ? ($localityCounty . ' County') : null,
        $localityState,
    ]);
    $localityLabel = empty($localityParts) ? null : implode(', ', $localityParts);

    $fmt = function ($value) {
        if ($value === null || $value === '') return '—';
        $n = (float) $value;
        if ($n >= 1_000_000_000) return '$' . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000) return '$' . number_format($n / 1_000_000, 2) . 'M';
        if ($n >= 1_000) return '$' . number_format($n / 1_000, 0) . 'K';
        return '$' . number_format($n);
    };
@endphp
<div>
    {{-- Loader --}}
    <div
        wire:loading.flex
        wire:target="runSearch, scanDocument"
        style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem;"
    >
        <svg style="width: 5rem; height: 5rem; color: #ef4444; animation: bi-spin 1s linear infinite;"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25;"></circle>
            <path fill="currentColor" style="opacity: 0.75;" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 style="margin-top: 1.5rem; margin-bottom: 0; font-size: 1.25rem; font-weight: 700; color: #111827;">
            Researching public budget data
        </h3>
        <p style="margin-top: 0.5rem; margin-bottom: 0; font-size: 0.875rem; color: #6b7280; text-align: center; max-width: 32rem; line-height: 1.5;">
            <span wire:loading wire:target="scanDocument">Reading the uploaded budget document and pulling the four headline figures.</span>
            <span wire:loading wire:target="runSearch">Claude is searching the web for {{ $localityLabel ?: 'this municipality' }}'s most recent budget and pulling the four headline figures.</span>
            This typically takes 30&ndash;120 seconds.
        </p>
        <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
            <span style="position: relative; display: inline-flex; width: 0.625rem; height: 0.625rem;">
                <span style="position: absolute; inset: 0; border-radius: 9999px; background-color: #f87171; opacity: 0.75; animation: bi-ping 1.4s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                <span style="position: relative; display: inline-block; width: 0.625rem; height: 0.625rem; border-radius: 9999px; background-color: #ef4444;"></span>
            </span>
            <span>Searching &mdash; please don't close this window</span>
        </div>
    </div>

    <div wire:loading.remove wire:target="runSearch">

        {{-- IDLE STATE --}}
        @if($state === 'idle')
            <div style="padding: 0.5rem 0;">
                @if($localityLabel)
                    <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        Target municipality
                    </p>
                    <p style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        {{ $localityLabel }}
                    </p>
                    @if($targetDepartment)
                        <p style="font-size: 0.8125rem; color: #6b7280; margin: 0 0 1.5rem 0;">
                            Targeting: <strong style="color: #374151;">{{ $targetDepartment }}</strong>
                        </p>
                    @else
                        <div style="height: 1.5rem;"></div>
                    @endif
                @else
                    <div style="padding: 1rem; background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <p style="font-size: 0.875rem; color: #854d0e; margin: 0;">
                            <strong>No locality on file.</strong> Rescan this RFP first so we can target the budget research at the right municipality.
                        </p>
                    </div>
                @endif

                {{-- Path A: Upload a budget document --}}
                <div style="padding: 1rem 1.125rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.625rem; margin-bottom: 0.875rem;">
                    <p style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        Option 1 &middot; Upload the Budget Document
                    </p>
                    <p style="font-size: 0.8125rem; color: #6b7280; line-height: 1.5; margin: 0 0 0.75rem 0;">
                        Most reliable. If you already have the adopted budget book or CIP PDF, attach it here and Claude will read it directly &mdash; no web guessing.
                    </p>

                    <input type="file" wire:model="budgetFile"
                           accept=".pdf,.doc,.docx,.txt,.csv,.md,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/csv,text/markdown"
                           style="display: block; width: 100%; font-size: 0.875rem; color: #374151; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #ffffff;">
                    @error('budgetFile')
                        <p style="color: #dc2626; font-size: 0.8125rem; margin: 0.5rem 0 0 0;">{{ $message }}</p>
                    @enderror
                    <p style="font-size: 0.75rem; color: #9ca3af; margin: 0.5rem 0 0 0;">
                        PDF, DOC, DOCX, TXT, CSV, MD (max 30MB). PDFs are read natively; other formats are text-extracted.
                    </p>
                    <span wire:loading wire:target="budgetFile" style="display: inline-block; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                        Uploading file&hellip;
                    </span>

                    <div style="margin-top: 0.875rem;">
                        <button type="button" wire:click="scanDocument"
                                wire:loading.attr="disabled" wire:target="budgetFile, scanDocument"
                                @if(! $budgetFile) disabled @endif
                                class="bi-primary-btn">
                            Scan This Document
                        </button>
                    </div>
                </div>

                {{-- Path B: Web search fallback --}}
                <div style="padding: 1rem 1.125rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.625rem; margin-bottom: 1rem;">
                    <p style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        Option 2 &middot; Skip the Upload &mdash; Search the Web
                    </p>
                    <p style="font-size: 0.8125rem; color: #6b7280; line-height: 1.5; margin: 0 0 0.75rem 0;">
                        No document handy? Claude will search the web for the municipality's most recent budget and CIP. Results vary by how publicly visible the budget is &mdash; small towns may only return press summaries.
                    </p>
                    <button type="button" wire:click="runSearch"
                            @if(! $localityLabel) disabled @endif
                            class="bi-secondary-btn">
                        Run Web Search Instead
                    </button>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="button"
                            x-on:click="$wire.$parent.call('unmountAction')"
                            class="bi-secondary-btn">
                        Cancel
                    </button>
                </div>
            </div>
        @endif

        {{-- COMPLETE STATE — slim view: just the two headline numbers + refresh controls --}}
        @if($state === 'complete' && $intel)
            @php
                $fiscalYear = $intel['fiscal_year'] ?? null;
                $yearSuffix = $fiscalYear ? ' (' . $fiscalYear . ')' : '';
            @endphp
            <div style="padding: 0.25rem 0;">

                {{-- Top row: Total Budget + CIP --}}
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                    <div style="padding: 1rem 1.125rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.625rem;">
                        <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.375rem 0;">
                            Total Budget{{ $yearSuffix }}
                        </p>
                        <p style="font-size: 1.75rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1;">
                            {{ $fmt($intel['total_budget'] ?? null) }}
                        </p>
                    </div>
                    <div style="padding: 1rem 1.125rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.625rem;">
                        <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.375rem 0;">
                            CIP / Capital{{ $yearSuffix }}
                        </p>
                        <p style="font-size: 1.75rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1;">
                            {{ $fmt($intel['cip_budget'] ?? null) }}
                        </p>
                    </div>
                </div>

                <p style="font-size: 0.75rem; color: #9ca3af; margin: 0 0 1rem 0;">
                    @if($intelAt) Last run {{ $intelAt }}@endif
                    @if($intelModel) &middot; {{ $intelModel }}@endif
                    &middot; full breakdown shown in the Budget section on this RFP's detail page.
                </p>

                {{-- Refresh: upload doc --}}
                <div style="padding: 1rem 1.125rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.625rem; margin-bottom: 0.875rem;">
                    <p style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        Refresh from a Budget Document
                    </p>
                    <p style="font-size: 0.8125rem; color: #6b7280; line-height: 1.5; margin: 0 0 0.75rem 0;">
                        Have the official budget book or CIP PDF? Upload it and Claude will replace these numbers with a clean read from the source.
                    </p>

                    <input type="file" wire:model="budgetFile"
                           accept=".pdf,.doc,.docx,.txt,.csv,.md,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/csv,text/markdown"
                           style="display: block; width: 100%; font-size: 0.875rem; color: #374151; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #ffffff;">
                    @error('budgetFile')
                        <p style="color: #dc2626; font-size: 0.8125rem; margin: 0.5rem 0 0 0;">{{ $message }}</p>
                    @enderror
                    <p style="font-size: 0.75rem; color: #9ca3af; margin: 0.5rem 0 0 0;">
                        PDF, DOC, DOCX, TXT, CSV, MD (max 30MB).
                    </p>
                    <span wire:loading wire:target="budgetFile" style="display: inline-block; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                        Uploading file&hellip;
                    </span>

                    <div style="margin-top: 0.875rem;">
                        <button type="button" wire:click="scanDocument"
                                wire:loading.attr="disabled" wire:target="budgetFile, scanDocument"
                                @if(! $budgetFile) disabled @endif
                                class="bi-primary-btn">
                            Scan This Document
                        </button>
                    </div>
                </div>

                {{-- Refresh: web search --}}
                <div style="padding: 1rem 1.125rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.625rem; margin-bottom: 1rem;">
                    <p style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                        Re-run the Web Search
                    </p>
                    <p style="font-size: 0.8125rem; color: #6b7280; line-height: 1.5; margin: 0 0 0.75rem 0;">
                        Claude will re-scan the web for the latest published budget for {{ $localityLabel ?: 'this municipality' }} and overwrite the numbers above.
                    </p>
                    <button type="button" wire:click="runSearch"
                            @if(! $localityLabel) disabled @endif
                            class="bi-secondary-btn">
                        Re-run Web Search
                    </button>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="button"
                            x-on:click="$wire.$parent.call('unmountAction')"
                            class="bi-primary-btn">
                        Done
                    </button>
                </div>
            </div>
        @endif

        {{-- FAILED STATE --}}
        @if($state === 'failed')
            <div style="padding: 1.5rem 1rem; text-align: center;">
                <svg style="width: 3rem; height: 3rem; color: #ef4444; margin: 0 auto; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 style="margin: 1rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #111827;">
                    Budget Search Failed
                </h3>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">{{ $errorMessage }}</p>
                <button type="button" wire:click="rerun" class="bi-primary-btn" style="margin-top: 1.25rem;">
                    Try Again
                </button>
            </div>
        @endif
    </div>

    <style>
        @keyframes bi-spin { to { transform: rotate(360deg); } }
        @keyframes bi-ping {
            75%, 100% { transform: scale(2.2); opacity: 0; }
        }
        .bi-primary-btn {
            background-color: #ef4444;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: background-color 0.15s ease;
        }
        .bi-primary-btn:hover:not(:disabled) {
            background-color: #111827;
        }
        .bi-primary-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .bi-secondary-btn {
            background-color: #ffffff;
            color: #374151;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            transition: background-color 0.15s ease;
        }
        .bi-secondary-btn:hover {
            background-color: #f3f4f6;
        }
    </style>
</div>
