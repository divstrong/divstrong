<?php

/*
|--------------------------------------------------------------------------
| Call booking
|--------------------------------------------------------------------------
|
| The availability the public booking page offers. Kept here rather than in the
| database because it changes about once a year and a wrong value is better caught
| in review than typed into a form at 11pm.
|
| Everything is expressed in the HOST's timezone — the times Jim actually works —
| and converted to the visitor's timezone for display. Slots are generated, not
| stored: a stored calendar of free slots goes stale the moment the rules change,
| and the only thing that genuinely has to persist is what somebody booked.
*/

return [
    // The timezone the windows below are written in.
    'timezone' => env('BOOKING_TIMEZONE', 'America/New_York'),

    'duration_minutes' => (int) env('BOOKING_DURATION', 30),

    // Gap left after a call before the next one can start.
    'buffer_minutes' => (int) env('BOOKING_BUFFER', 15),

    // Nobody can book a call starting in the next N hours: a meeting that appears in
    // the calendar twenty minutes before it starts is a meeting that gets missed.
    'min_notice_hours' => (int) env('BOOKING_MIN_NOTICE', 12),

    // How far ahead the page will offer. Long enough to find a slot, short enough
    // that the commitment still feels real.
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS', 21),

    /*
    | Bookable windows per weekday, in the timezone above. 24-hour times, and a day
    | with no windows is simply not offered. Several windows per day are allowed —
    | that is how you keep a lunch hour.
    */
    'hours' => [
        'monday' => [['09:00', '12:00'], ['13:00', '17:00']],
        'tuesday' => [['09:00', '12:00'], ['13:00', '17:00']],
        'wednesday' => [['09:00', '12:00'], ['13:00', '17:00']],
        'thursday' => [['09:00', '12:00'], ['13:00', '17:00']],
        'friday' => [['09:00', '12:00'], ['13:00', '16:00']],
        'saturday' => [],
        'sunday' => [],
    ],

    // Dates nobody can book, whatever the windows say. 'Y-m-d' in the host timezone.
    'blackout_dates' => array_filter(explode(',', (string) env('BOOKING_BLACKOUT_DATES', ''))),

    // Who the invitation comes from and who gets told about a booking.
    'host' => [
        'name' => env('BOOKING_HOST_NAME', 'Jim Cotter'),
        'email' => env('BOOKING_HOST_EMAIL', 'jim@divstrong.com'),
        'title' => env('BOOKING_HOST_TITLE', 'divStrong'),
    ],

    /*
    | The address the public pages point people at.
    |
    | Deliberately not the host's own: a shared inbox is answered when one person is on
    | holiday, and an unanswered "questions?" is worse than not offering one.
    */
    'contact_email' => env('BOOKING_CONTACT_EMAIL', 'hello@divstrong.com'),

    // Shown on the booking page and in the invite so nobody wonders how the call happens.
    'location' => env('BOOKING_LOCATION', 'Phone call — we will ring the number you give us'),
];
