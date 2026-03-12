<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
    'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
];
