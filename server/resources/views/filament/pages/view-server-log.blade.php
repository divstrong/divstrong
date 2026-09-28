@php
    $log = $this->logFile();
    $result = $this->entries();
    $entries = $result['entries'];

    // The raw text of exactly what is on screen, so "Copy all" hands over what is shown.
    $raw = collect($entries)->pluck('text')->implode("\n");
@endphp

{{-- Styles live here rather than in Tailwind classes: this panel has no custom Filament
     theme, so the admin CSS build carries only Filament's own utilities. --}}
@push('styles')
<style>
    .dsl-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
        justify-content: space-between;
        padding: .75rem 1rem; margin-bottom: 1rem;
        background: #fff; border: 1px solid rgb(0 0 0 / .07); border-radius: .75rem;
    }
    .dark .dsl-bar { background: rgb(255 255 255 / .04); border-color: rgb(255 255 255 / .1); }

    .dsl-controls { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }

    .dsl-bar select, .dsl-bar input {
        font-size: .8125rem; line-height: 1.25rem; color: #111827;
        background: #fff; border: 1px solid #d1d5db; border-radius: .5rem;
        padding: .4rem .6rem; outline: none;
    }
    .dsl-bar input { min-width: 14rem; }
    .dsl-bar select:focus, .dsl-bar input:focus { border-color: #ed2537; box-shadow: 0 0 0 1px #ed2537; }
    .dark .dsl-bar select, .dark .dsl-bar input {
        color: #f3f4f6; background: rgb(255 255 255 / .05); border-color: rgb(255 255 255 / .12);
    }

    .dsl-meta { font-size: .8125rem; color: #6b7280; display: flex; gap: .5rem; align-items: center; }
    .dark .dsl-meta { color: #9ca3af; }
    .dsl-note { margin: .75rem 0 0; font-size: .75rem; color: #6b7280; }
    .dark .dsl-note { color: #9ca3af; }

    .dsl-term {
        background: #0b0f14; border-radius: .75rem; overflow: hidden;
        border: 1px solid rgb(255 255 255 / .08);
    }
    .dsl-term-bar {
        display: flex; align-items: center; gap: .75rem;
        padding: .6rem .9rem; background: #11161d; border-bottom: 1px solid rgb(255 255 255 / .08);
    }
    .dsl-dots { display: flex; gap: .375rem; flex-shrink: 0; }
    .dsl-dots span { width: .7rem; height: .7rem; border-radius: 9999px; display: block; }
    .dsl-name {
        flex: 1; min-width: 0; margin: 0; text-align: center;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .75rem; color: #8b949e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dsl-copy {
        display: inline-flex; align-items: center; gap: .375rem; flex-shrink: 0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .75rem;
        color: #c9d1d9; background: rgb(255 255 255 / .06); border: 0; border-radius: .375rem;
        padding: .35rem .6rem; cursor: pointer; transition: background .15s;
    }
    .dsl-copy:hover { background: rgb(255 255 255 / .12); }
    .dsl-copy svg { width: .85rem; height: .85rem; }

    .dsl-out { max-height: 70vh; overflow: auto; padding: .6rem .5rem; }

    .dsl-entry { position: relative; border-radius: .375rem; padding: .3rem .6rem; }
    .dsl-entry:hover { background: rgb(255 255 255 / .04); }
    .dsl-entry pre {
        margin: 0; padding-left: .75rem; border-left: 2px solid currentColor;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12.5px; line-height: 1.65;
        white-space: pre-wrap; word-break: break-word;
    }
    .dsl-entry-copy {
        position: absolute; inset-inline-end: .5rem; top: .4rem; display: none;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 10px;
        color: #c9d1d9; background: rgb(255 255 255 / .1); border: 0; border-radius: .25rem;
        padding: .2rem .45rem; cursor: pointer;
    }
    .dsl-entry:hover .dsl-entry-copy { display: block; }
    .dsl-entry-copy:hover { background: rgb(255 255 255 / .2); }

    .dsl-error pre   { color: #ff7b72; }
    .dsl-warning pre { color: #e3b341; }
    .dsl-info pre    { color: #79c0ff; }
    .dsl-debug pre   { color: #8b949e; }
    .dsl-plain pre   { color: #c9d1d9; border-left-color: rgb(255 255 255 / .15); }

    .dsl-empty {
        padding: 2.5rem 1rem; text-align: center; color: #6e7681;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .875rem;
    }
</style>
@endpush

<x-filament-panels::page>
    <div x-data="{
            copied: false,
            copy(text) {
                const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1600); };
                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(() => this.fallback(text, done));
                } else {
                    this.fallback(text, done);
                }
            },
            // The Clipboard API needs a secure context; an admin session over plain HTTP falls back.
            fallback(text, done) {
                const area = document.createElement('textarea');
                area.value = text;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(area);
                done();
            },
         }">

        {{-- Controls --}}
        <div class="dsl-bar">
            <div class="dsl-controls">
                <select wire:model.live="level" aria-label="Log level">
                    @foreach($this->levelOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="lines" aria-label="Lines to read">
                    @foreach($this->lineOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Filter text…" aria-label="Filter log text">
            </div>

            <div class="dsl-meta">
                <span>{{ number_format(count($entries)) }} {{ Str::plural('entry', count($entries)) }}</span>
                <span>·</span>
                <span>{{ \Illuminate\Support\Number::fileSize($log['size'], precision: 1) }}</span>
            </div>

            @if($result['truncated'])
                <p class="dsl-note" style="width: 100%;">
                    Showing the last {{ number_format($this->lines) }} lines of this file. Download it for the full history.
                </p>
            @endif
        </div>

        {{-- Terminal --}}
        <div class="dsl-term">
            <div class="dsl-term-bar">
                <div class="dsl-dots">
                    <span style="background:#ff5f57"></span>
                    <span style="background:#febc2e"></span>
                    <span style="background:#28c840"></span>
                </div>

                <p class="dsl-name">{{ $log['name'] }}</p>

                <button type="button" class="dsl-copy" x-on:click="copy(@js($raw))">
                    <svg x-show="! copied" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg x-show="copied" x-cloak fill="none" stroke="#28c840" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="copied ? 'Copied' : 'Copy all'">Copy all</span>
                </button>
            </div>

            <div class="dsl-out">
                @forelse($entries as $entry)
                    @php
                        $tone = match ($entry['level']) {
                            'error', 'critical', 'alert', 'emergency' => 'dsl-error',
                            'warning' => 'dsl-warning',
                            'info', 'notice' => 'dsl-info',
                            'debug' => 'dsl-debug',
                            default => 'dsl-plain',
                        };
                    @endphp

                    <div class="dsl-entry {{ $tone }}">
                        <button type="button" class="dsl-entry-copy" title="Copy this entry" x-on:click="copy(@js($entry['text']))">Copy</button>
                        <pre>{{ $entry['text'] }}</pre>
                    </div>
                @empty
                    <p class="dsl-empty">
                        @if(filled($this->search) || $this->level !== 'all')
                            Nothing in the last {{ number_format($this->lines) }} lines matches these filters.
                        @else
                            This log file is empty.
                        @endif
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
