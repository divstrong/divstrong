<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),

    // Opus 5 thinks by default, and max_tokens caps thinking + the answer
    // together — 4096 truncates long RFP extractions mid-JSON.
    'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 16000),

    // Thinking depth / token spend: low | medium | high | xhigh | max.
    // 'high' is the API default; drop to 'medium' to cut cost and latency.
    'effort' => env('ANTHROPIC_EFFORT', 'high'),
];
