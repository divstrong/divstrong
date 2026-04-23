@php
    $record = $getRecord();
    $score = $record->score;
    $label = $record->score_label;

    $scoreColor = match(true) {
        $score === null => 'gray',
        $score >= 75 => 'green',
        $score >= 60 => 'yellow',
        default => 'red',
    };

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

<div style="display: flex; justify-content: flex-end;">
    <div style="border-radius: 0.75rem; border: 1px solid; padding: 1rem 1.75rem; text-align: center; {{ $scoreBg }}">
        <p style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem 0; {{ $scoreText }} opacity: 0.7;">
            Fit Score
        </p>
        <p style="font-size: 3rem; font-weight: 800; line-height: 1; margin: 0; {{ $scoreText }}">
            {{ $score !== null ? $score : '—' }}<span style="font-size: 1.125rem; font-weight: 500;">/100</span>
        </p>
        <p style="font-size: 0.875rem; font-weight: 600; margin: 0.25rem 0 0 0; {{ $scoreText }} opacity: 0.8;">
            {{ $score !== null ? $label : 'Pending' }}
        </p>
    </div>
</div>
