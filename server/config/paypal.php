<?php

return [
    // Trimmed: a space pasted after a key is sent to PayPal as part of it, and the result is
    // the same "Client Authentication failed" as a wrong key, with nothing to tell them apart.
    'client_id' => trim((string) env('PAYPAL_CLIENT_ID', '')),
    'client_secret' => trim((string) env('PAYPAL_CLIENT_SECRET', '')),
    'mode' => env('PAYPAL_MODE', 'sandbox'),

    'base_url' => env('PAYPAL_MODE', 'sandbox') === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com',

    'sdk_url' => env('PAYPAL_MODE', 'sandbox') === 'live'
        ? 'https://www.paypal.com/sdk/js'
        : 'https://www.sandbox.paypal.com/sdk/js',
];
