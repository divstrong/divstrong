<div>
    {{-- Loader: shown during the synchronous screen request --}}
    <div
        wire:loading.flex
        wire:target="screen"
        style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem;"
    >
        <svg style="width: 5rem; height: 5rem; color: #ef4444; animation: rfp-spin 1s linear infinite;"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25;"></circle>
            <path fill="currentColor" style="opacity: 0.75;" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <h3 style="margin-top: 1.5rem; margin-bottom: 0; font-size: 1.25rem; font-weight: 700; color: #111827;">
            Analyzing your RFP
        </h3>

        <p style="margin-top: 0.5rem; margin-bottom: 0; font-size: 0.875rem; color: #6b7280; text-align: center; max-width: 28rem; line-height: 1.5;">
            Claude is reading the document, scoring fit, extracting requirements, and flagging risks.
            This typically takes 30&ndash;60 seconds depending on document length.
        </p>

        <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
            <span style="position: relative; display: inline-flex; width: 0.625rem; height: 0.625rem;">
                <span style="position: absolute; inset: 0; border-radius: 9999px; background-color: #f87171; opacity: 0.75; animation: rfp-ping 1.4s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                <span style="position: relative; display: inline-block; width: 0.625rem; height: 0.625rem; border-radius: 9999px; background-color: #ef4444;"></span>
            </span>
            <span>Processing &mdash; please don't close this window</span>
        </div>
    </div>

    {{-- Form / result / error --}}
    <div wire:loading.remove wire:target="screen">

        {{-- FORM STATE --}}
        @if($state === 'form')
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                {{-- File upload --}}
                <div>
                    <label for="rfp-file" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        RFP Document <span style="color: #ef4444;">*</span>
                    </label>
                    <input id="rfp-file" type="file" wire:model="file" required
                           accept=".pdf,.doc,.docx,.txt,.csv,.md,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/csv,text/markdown"
                           style="display: block; width: 100%; font-size: 0.875rem; color: #374151; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #ffffff;">
                    @error('file')
                        <p style="color: #dc2626; font-size: 0.8125rem; margin: 0.5rem 0 0 0;">{{ $message }}</p>
                    @enderror
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0.5rem 0 0 0;">
                        Accepted formats: PDF, DOC, DOCX, TXT, CSV, MD (max 20MB).
                    </p>
                    <span wire:loading wire:target="file" style="display: inline-block; font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                        Uploading file&hellip;
                    </span>
                </div>

                {{-- Analysis Prompt (collapsible) --}}
                <details style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.75rem 1rem; background-color: #f9fafb;">
                    <summary style="cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #374151;">
                        Analysis Prompt
                        <span style="font-weight: 400; color: #6b7280; font-size: 0.8125rem; margin-left: 0.25rem;">
                            &mdash; Customize the prompt sent to Claude. Leave as-is for the standard RFP screening analysis.
                        </span>
                    </summary>
                    <textarea wire:model="prompt" rows="12"
                              style="display: block; width: 100%; margin-top: 0.75rem; font-size: 0.8125rem; color: #374151; padding: 0.625rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #ffffff; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; line-height: 1.5; resize: vertical;"></textarea>
                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0.5rem 0 0 0;">
                        The document contents will be appended automatically.
                    </p>
                </details>

                {{-- Buttons --}}
                <div style="display: flex; align-items: center; gap: 0.625rem;">
                    <button type="button" wire:click="screen" wire:loading.attr="disabled" wire:target="file, screen"
                            class="rfp-rescan-primary-btn">
                        Screen
                    </button>
                    <button type="button"
                            x-on:click="$wire.$parent.call('unmountAction')"
                            class="rfp-rescan-secondary-btn">
                        Cancel
                    </button>
                </div>
            </div>
        @endif

        {{-- COMPLETE STATE --}}
        @if($state === 'complete')
            @php
                $scoreColor = $score === null ? 'gray' : ($score >= 75 ? 'green' : ($score >= 60 ? 'yellow' : 'red'));
                $scoreBg = match($scoreColor) {
                    'green' => 'background-color: #dcfce7; border-color: #bbf7d0;',
                    'yellow' => 'background-color: #fef9c3; border-color: #fde68a;',
                    'red' => 'background-color: #fee2e2; border-color: #fecaca;',
                    default => 'background-color: #f3f4f6; border-color: #e5e7eb;',
                };
                $scoreText = match($scoreColor) {
                    'green' => 'color: #166534;',
                    'yellow' => 'color: #854d0e;',
                    'red' => 'color: #991b1b;',
                    default => 'color: #374151;',
                };
            @endphp

            <div style="display: flex; flex-direction: column; align-items: center; padding: 1.5rem 1rem;">
                <div style="border-radius: 0.875rem; border: 1px solid; padding: 1.25rem 2.5rem; text-align: center; {{ $scoreBg }}">
                    <p style="font-size: 4rem; font-weight: 800; line-height: 1; margin: 0; {{ $scoreText }}">
                        {{ $score ?? '—' }}<span style="font-size: 1.5rem; font-weight: 500;">/100</span>
                    </p>
                </div>

                <p style="margin: 0.875rem 0 0 0; font-size: 1rem; font-weight: 700; {{ $scoreText }}">
                    {{ $scoreLabel ?? 'Pending' }}
                </p>

                @if($modelLabel)
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #9ca3af;">
                        Scanned with {{ $modelLabel }}
                    </p>
                @endif

                <h3 style="margin: 1.25rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #111827;">
                    Screening Complete
                </h3>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #6b7280; text-align: center; max-width: 26rem;">
                    The RFP has been analyzed. Open the detail page to review the score, red flags, and requirements.
                </p>

                <a href="{{ $viewUrl }}"
                   style="margin-top: 1.5rem; background-color: #ef4444; color: white; padding: 0.625rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    View Details
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <button type="button" wire:click="reset_"
                        style="margin-top: 0.75rem; background: transparent; color: #6b7280; padding: 0.375rem 0.75rem; border: none; cursor: pointer; font-size: 0.75rem; text-decoration: underline;">
                    Screen another RFP
                </button>
            </div>
        @endif

        {{-- FAILED STATE --}}
        @if($state === 'failed')
            <div style="padding: 1.5rem 1rem; text-align: center;">
                <svg style="width: 3rem; height: 3rem; color: #ef4444; margin: 0 auto; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 style="margin: 1rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #111827;">
                    Screening Failed
                </h3>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">{{ $errorMessage }}</p>
                <button type="button" wire:click="reset_"
                        style="margin-top: 1.25rem; background-color: #ef4444; color: white; padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer; font-weight: 600; font-size: 0.875rem;">
                    Try Again
                </button>
            </div>
        @endif
    </div>

    <style>
        @keyframes rfp-spin { to { transform: rotate(360deg); } }
        @keyframes rfp-ping {
            75%, 100% { transform: scale(2.2); opacity: 0; }
        }
        .rfp-rescan-primary-btn {
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
        .rfp-rescan-primary-btn:hover:not(:disabled) {
            background-color: #111827;
        }
        .rfp-rescan-primary-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .rfp-rescan-secondary-btn {
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
        .rfp-rescan-secondary-btn:hover {
            background-color: #f3f4f6;
        }
    </style>
</div>
