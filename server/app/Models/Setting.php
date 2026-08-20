<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_name', 'email', 'phone',
        'address1', 'address2', 'city', 'state', 'zip',
        'logo',
        'hourly_rate', 'daily_rate', 'sprint_rate', 'hours_per_day',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'sprint_rate' => 'decimal:2',
            'hours_per_day' => 'integer',
        ];
    }

    /** Memoised for the request — the public site reads rates on every page load. */
    private static ?self $cached = null;

    public static function instance(): static
    {
        return static::$cached ??= static::firstOrCreate([]);
    }

    public static function forgetInstance(): void
    {
        static::$cached = null;
    }

    /**
     * The one place billing rates come from: the Rates tab in admin settings.
     * Drives both the public pricing section and proposal generation.
     */
    public static function rates(): array
    {
        $settings = static::instance();

        return [
            'hour' => (float) ($settings->hourly_rate ?? config('proposals.fallback_rates.hour')),
            'day' => (float) ($settings->daily_rate ?? config('proposals.fallback_rates.day')),
            'sprint' => (float) ($settings->sprint_rate ?? config('proposals.fallback_rates.sprint')),
            'hours_per_day' => (int) ($settings->hours_per_day ?: config('proposals.fallback_rates.hours_per_day')),
        ];
    }

    public static function rateFor(string $unit): float
    {
        $rates = static::rates();

        return $rates[$unit] ?? $rates['sprint'];
    }
}
