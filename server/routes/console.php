<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| Requires one cron entry on the server:
|
|   * * * * * cd /path/to/server && php artisan schedule:run >> /dev/null 2>&1
|
| Without it nothing below ever runs, and a drip campaign silently stops after
| its first manual send.
*/

// Drip campaigns. Every fifteen minutes so a step due at 09:00 goes out near 09:00;
// the command enforces its own weekday sending window and per-run cap.
Schedule::command('campaigns:dispatch')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
