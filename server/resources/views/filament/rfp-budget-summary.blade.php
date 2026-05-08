@php
    $record = $getRecord();
    $intel = $record->budget_intel ?? [];
    if (empty($intel)) return;

    $fmt = function ($value) {
        if ($value === null || $value === '') return '—';
        $n = (float) $value;
        if ($n >= 1_000_000_000) return '$' . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000) return '$' . number_format($n / 1_000_000, 2) . 'M';
        if ($n >= 1_000) return '$' . number_format($n / 1_000, 0) . 'K';
        return '$' . number_format($n);
    };

    $localityLabel = $record->locality_label;
    $fiscalYear = $intel['fiscal_year'] ?? null;
    $yearSuffix = $fiscalYear ? ' (' . $fiscalYear . ')' : '';

    $hasTechAside = ! empty($intel['tech_set_aside']) || ! empty($intel['tech_set_aside_notes']);
    $hasDeptAside = ! empty($intel['department_set_aside']) || ! empty($intel['department_set_aside_notes']);
    $deptLabel = ! empty($intel['department_name']) ? ($intel['department_name'] . ' Budget') : 'Department Set-Aside';
@endphp

<div>
    {{-- Top row: Total Budget + CIP --}}
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.625rem; margin-bottom: 0.875rem;">
        <div style="padding: 0.875rem 1rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                Total Budget{{ $yearSuffix }}
            </p>
            <p style="font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1;">
                {{ $fmt($intel['total_budget'] ?? null) }}
            </p>
        </div>
        <div style="padding: 0.875rem 1rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
            <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0;">
                CIP / Capital{{ $yearSuffix }}
            </p>
            <p style="font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1;">
                {{ $fmt($intel['cip_budget'] ?? null) }}
            </p>
        </div>
    </div>

    {{-- Summary --}}
    <div style="padding: 0.875rem 1rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.875rem;">
        <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0 0 0.375rem 0;">
            {{ $fiscalYear ?: 'Latest budget' }} &middot; {{ $localityLabel ?: 'Unknown locality' }}
            @if(! empty($intel['department_name']))
                &middot; {{ $intel['department_name'] }}
            @endif
        </p>
        <p style="font-size: 0.875rem; color: #111827; margin: 0; line-height: 1.55;">
            {{ $intel['summary'] ?: 'No summary available.' }}
        </p>
    </div>

    {{-- Stacked full-width set-asides --}}
    @if($hasTechAside)
        <div style="padding: 0.875rem 1rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.625rem;">
            <div style="display: flex; align-items: baseline; gap: 1rem; flex-wrap: wrap;">
                <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0; flex: 1; min-width: 0;">
                    Technology Set-Aside{{ $yearSuffix }}
                </p>
                <p style="font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1; flex-shrink: 0;">
                    {{ $fmt($intel['tech_set_aside'] ?? null) }}
                </p>
            </div>
            @if(! empty($intel['tech_set_aside_notes']))
                <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #6b7280; line-height: 1.5;">
                    {{ $intel['tech_set_aside_notes'] }}
                </p>
            @endif
        </div>
    @endif

    @if($hasDeptAside)
        <div style="padding: 0.875rem 1rem; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.625rem;">
            <div style="display: flex; align-items: baseline; gap: 1rem; flex-wrap: wrap;">
                <p style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #111827; margin: 0; flex: 1; min-width: 0;">
                    {{ $deptLabel }}{{ $yearSuffix }}
                </p>
                <p style="font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; line-height: 1.1; flex-shrink: 0;">
                    {{ $fmt($intel['department_set_aside'] ?? null) }}
                </p>
            </div>
            @if(! empty($intel['department_set_aside_notes']))
                <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #6b7280; line-height: 1.5;">
                    {{ $intel['department_set_aside_notes'] }}
                </p>
            @endif
        </div>
    @endif

    {{-- Source + meta --}}
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 0.75rem; color: #6b7280; margin-top: 0.875rem;">
        <div>
            @if(($intel['source_method'] ?? null) === 'document' && ! empty($intel['source_file_path']))
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($intel['source_file_path']) }}"
                   target="_blank" rel="noopener"
                   style="color: #ef4444; text-decoration: underline; font-weight: 500;">
                    {{ $intel['source_filename'] ?? 'Source budget document' }} &rarr;
                </a>
            @elseif(! empty($intel['source_url']))
                <a href="{{ $intel['source_url'] }}" target="_blank" rel="noopener"
                   style="color: #ef4444; text-decoration: underline; font-weight: 500;">
                    Source budget document &rarr;
                </a>
            @elseif(($intel['source_method'] ?? null) === 'document' && ! empty($intel['source_filename']))
                <span>Scanned: <strong style="color: #374151;">{{ $intel['source_filename'] }}</strong></span>
            @endif
        </div>
        <div>
            @if($record->budget_intel_at) Run {{ $record->budget_intel_at->toDayDateTimeString() }}@endif
            @if($record->budget_intel_model) &middot; {{ $record->budget_intel_model }}@endif
        </div>
    </div>

    @if(! empty($intel['search_notes']))
        <p style="font-size: 0.75rem; color: #6b7280; margin: 0.625rem 0 0 0; padding: 0.5rem 0.75rem; background-color: #f9fafb; border-radius: 0.375rem; border-left: 3px solid #d1d5db;">
            <strong style="color: #374151;">Notes:</strong> {{ $intel['search_notes'] }}
        </p>
    @endif
</div>
