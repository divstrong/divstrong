<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Units
    |--------------------------------------------------------------------------
    |
    | A proposal drafted from a screened RFP is sold in ONE of these units.
    | The engagement is divided into phases; each phase becomes a category in
    | the Scope of Work section and a single line item in the Investment
    | section, billed at the unit rate from Admin > Settings > Rates.
    |
    | units_per_phase — the natural size of one phase in this unit, used to
    | derive how many phases an engagement of a given size should have.
    |
    */

    'units' => [
        'sprint' => [
            'label' => 'Sprint',
            'phase_label' => 'Sprint',
            'rate_field' => 'sprint_rate',
            'units_per_phase' => 1,
            'max_quantity' => 24,
            'max_phases' => 24,
            'blurb' => 'a two-week block of focused delivery by a small senior team',
        ],
        'day' => [
            'label' => 'Day',
            'phase_label' => 'Phase',
            'rate_field' => 'daily_rate',
            'units_per_phase' => 5,
            'max_quantity' => 120,
            'max_phases' => 8,
            'blurb' => 'a dedicated day of design and build time',
        ],
        'hour' => [
            'label' => 'Hour',
            'phase_label' => 'Phase',
            'rate_field' => 'hourly_rate',
            'units_per_phase' => 40,
            'max_quantity' => 2000,
            'max_phases' => 8,
            'blurb' => 'an hour of senior engineering time',
        ],
    ],

    'default_unit' => env('PROPOSAL_DEFAULT_UNIT', 'sprint'),

    'default_quantity' => [
        'sprint' => 4,
        'day' => 10,
        'hour' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Rates
    |--------------------------------------------------------------------------
    |
    | Only used before the settings row has been populated. The live rates are
    | edited in Admin > Settings > Rates.
    |
    */

    'fallback_rates' => [
        'hour' => 175.0,
        'day' => 1000.0,
        'sprint' => 3000.0,
        'hours_per_day' => 10,
    ],
];
