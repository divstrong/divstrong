<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),

        /*
         * Shared secret for the event webhook (opens/clicks/bounces/spam complaints), which
         * is what makes the engagement chips on Prospects mean anything.
         *
         * Postmark supports HTTP Basic Auth embedded in the webhook URL, so configure it as
         *   https://user:secret@divstrong.com/api/webhooks/postmark
         * and set POSTMARK_WEBHOOK_SECRET to match `secret`. Unset, the endpoint rejects
         * everything rather than accepting unauthenticated posts.
         */
        'webhook_secret' => env('POSTMARK_WEBHOOK_SECRET'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Google Places — the cheap half of "Find Prospects".
    |
    | Places enumerates real businesses by category and metro for a fraction of a cent per
    | twenty, against the six figures of tokens a single web-search round costs to establish
    | the same facts. It has no owner field, so the contact name is recovered from the
    | agency's own site afterwards by NameFinder, which is free because those pages are being
    | fetched anyway.
    |
    | Reads GOOGLE_MAPS_API_KEY first, since a Maps-enabled key normally covers Places too,
    | and falls back to a Places-specific name. Needs "Places API (New)" enabled on the
    | project: a key that only has the legacy Places API answers 403.
    |
    | Without it, Find Prospects still works — it falls back to the Claude web-search source,
    | which costs tokens instead.
    */
    'google' => [
        'places_key' => env('GOOGLE_MAPS_API_KEY', env('GOOGLE_PLACES_API_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Proposal PDF rendering (Puppeteer)
    |--------------------------------------------------------------------------
    |
    | The proposal PDF is produced by driving headless Chrome over the real
    | proposal page, so the download matches what the client sees in the browser.
    |
    | The web process (php-fpm / artisan serve) usually hands the child process a
    | stripped environment with no TEMP or HOME, which leaves Node resolving its
    | temp dir to "undefined\temp" and Puppeteer unable to find its Chrome
    | download. The controller therefore passes these explicitly.
    |
    | base_url only matters when the app cannot serve a second request while the
    | PDF request is in flight -- notably `php artisan serve` on Windows, which is
    | single-threaded and would otherwise deadlock against itself. Point it at a
    | second local server (e.g. http://localhost:8001) during development.
    */
    'pdf' => [
        'node_binary' => env('NODE_BINARY', 'node'),
        'chrome_path' => env('PUPPETEER_EXECUTABLE_PATH'),
        'cache_dir' => env('PUPPETEER_CACHE_DIR'),
        'base_url' => env('PDF_RENDER_BASE_URL'),
        'timeout' => (int) env('PDF_RENDER_TIMEOUT', 180),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
