<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * How a proposal drafted from a client brief is sold: a number of sprints —
 * each one a delivery phase, a Scope of Work category, its own Investment row
 * and its own payment milestone — plus optional blocks of day-rate and
 * hour-rate development time that ride along as single extra Investment rows.
 *
 * The sibling of {@see EngagementPlan}, which sells a single unit and is used
 * for RFP-derived proposals.
 */
class EngagementMix
{
    private function __construct(
        public readonly int $sprints,
        public readonly int $days,
        public readonly int $hours,
        public readonly float $sprintRate,
        public readonly float $dayRate,
        public readonly float $hourRate,
        public readonly ?string $scopePrompt,
    ) {
    }

    /** Rates always come from Settings → Rates unless a caller overrides them. */
    public static function make(
        mixed $sprints,
        mixed $days = 0,
        mixed $hours = 0,
        ?string $scopePrompt = null,
        ?array $rates = null,
    ): self {
        $rates ??= Setting::rates();

        $scopePrompt = is_string($scopePrompt) ? trim($scopePrompt) : null;

        return new self(
            sprints: static::clamp($sprints, 'sprint', min: 1),
            days: static::clamp($days, 'day'),
            hours: static::clamp($hours, 'hour'),
            sprintRate: (float) ($rates['sprint'] ?? 0),
            dayRate: (float) ($rates['day'] ?? 0),
            hourRate: (float) ($rates['hour'] ?? 0),
            scopePrompt: $scopePrompt === '' ? null : $scopePrompt,
        );
    }

    /** The per-unit ceiling from config/proposals.php, so a typo can't bill 9,999 sprints. */
    public static function maxQuantity(string $unit): int
    {
        return (int) (config("proposals.units.{$unit}.max_quantity") ?: 100);
    }

    private static function clamp(mixed $value, string $unit, int $min = 0): int
    {
        return max($min, min(static::maxQuantity($unit), (int) $value));
    }

    public function sprintSubtotal(): float
    {
        return $this->sprints * $this->sprintRate;
    }

    public function daySubtotal(): float
    {
        return $this->days * $this->dayRate;
    }

    public function hourSubtotal(): float
    {
        return $this->hours * $this->hourRate;
    }

    public function total(): float
    {
        return $this->sprintSubtotal() + $this->daySubtotal() + $this->hourSubtotal();
    }

    public function hasDays(): bool
    {
        return $this->days > 0;
    }

    public function hasHours(): bool
    {
        return $this->hours > 0;
    }

    public function hasSupplemental(): bool
    {
        return $this->hasDays() || $this->hasHours();
    }

    /** House style, per the existing proposals: "Sprint #2". */
    public function sprintLabel(int $number): string
    {
        return 'Sprint #' . $number;
    }

    /** "a two-week block of focused delivery by a small senior team" */
    public function sprintBlurb(): string
    {
        return (string) (config('proposals.units.sprint.blurb')
            ?: 'a two-week block of focused delivery by a small senior team');
    }

    /** "4 sprints at $3,000 each" */
    public function sprintLine(): string
    {
        return $this->sprints . ' ' . Str::plural('sprint', $this->sprints)
            . ' at $' . number_format($this->sprintRate, 0) . ' each';
    }

    /** "10 days at $1,250 each", or null when none were bought. */
    public function dayLine(): ?string
    {
        return $this->hasDays()
            ? $this->days . ' ' . Str::plural('day', $this->days) . ' at $' . number_format($this->dayRate, 0) . ' each'
            : null;
    }

    public function hourLine(): ?string
    {
        return $this->hasHours()
            ? $this->hours . ' ' . Str::plural('hour', $this->hours) . ' at $' . number_format($this->hourRate, 0) . ' each'
            : null;
    }

    /** "4 sprints at $3,000 each, 10 days at $1,250 each — $17,500 total" */
    public function summaryLine(): string
    {
        $parts = array_filter([$this->sprintLine(), $this->dayLine(), $this->hourLine()]);

        return implode(', ', $parts) . ' — $' . number_format($this->total(), 0) . ' total';
    }
}
