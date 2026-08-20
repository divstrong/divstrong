<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * How an RFP-derived proposal is sold: one billing unit (sprint, day, or
 * hour), a quantity of it, and the number of delivery phases that quantity is
 * split across. One phase = one Scope of Work category + one Investment row.
 */
class EngagementPlan
{
    private function __construct(
        public readonly string $unit,
        public readonly int $quantity,
        public readonly float $rate,
        public readonly int $phaseCount,
        public readonly ?string $scopePrompt,
    ) {
    }

    public static function make(
        ?string $unit,
        ?int $quantity,
        ?string $scopePrompt = null,
        ?float $rate = null,
    ): self {
        $unit = static::normaliseUnit($unit);
        $config = static::configFor($unit);

        $quantity = max(1, min((int) $config['max_quantity'], (int) ($quantity ?: static::defaultQuantity($unit))));

        $scopePrompt = is_string($scopePrompt) ? trim($scopePrompt) : null;

        return new self(
            unit: $unit,
            quantity: $quantity,
            rate: $rate ?? Setting::rateFor($unit),
            phaseCount: static::phaseCountFor($unit, $quantity),
            scopePrompt: $scopePrompt === '' ? null : $scopePrompt,
        );
    }

    public static function normaliseUnit(?string $unit): string
    {
        $unit = strtolower(trim((string) $unit));
        $unit = Str::singular($unit);

        return array_key_exists($unit, static::units())
            ? $unit
            : (string) config('proposals.default_unit', 'sprint');
    }

    public static function units(): array
    {
        return (array) config('proposals.units', []);
    }

    public static function configFor(string $unit): array
    {
        return static::units()[$unit] ?? static::units()['sprint'];
    }

    public static function defaultQuantity(string $unit): int
    {
        return (int) (config("proposals.default_quantity.{$unit}") ?: 1);
    }

    /**
     * A day engagement of 10 days reads better as two 5-day phases than as ten
     * one-day ones, so quantity is folded into phases of a natural size.
     */
    public static function phaseCountFor(string $unit, int $quantity): int
    {
        $config = static::configFor($unit);
        $perPhase = max(1, (int) $config['units_per_phase']);

        return (int) max(1, min(
            (int) $config['max_phases'],
            (int) ceil($quantity / $perPhase),
            $quantity,
        ));
    }

    public function config(): array
    {
        return static::configFor($this->unit);
    }

    public function total(): float
    {
        return $this->quantity * $this->rate;
    }

    public function unitLabel(?int $count = null): string
    {
        return Str::plural(strtolower($this->config()['label']), $count ?? $this->quantity);
    }

    public function phaseLabel(int $number): string
    {
        return $this->config()['phase_label'] . ' ' . $number;
    }

    public function blurb(): string
    {
        return (string) $this->config()['blurb'];
    }

    /** "4 sprints at $3,000 each" */
    public function quantityLabel(): string
    {
        return $this->quantity . ' ' . $this->unitLabel() . ' at $' . number_format($this->rate, 0) . ' each';
    }

    /**
     * Spread the engagement's units across its phases, honouring the weights
     * the drafter proposed while guaranteeing the phases sum to exactly the
     * quantity the user bought — the Investment total has to be exact.
     *
     * @param  array<int, int|float>  $weights  one desired quantity per phase
     * @return array<int, int>
     */
    public function allocate(array $weights): array
    {
        $count = $this->phaseCount;

        $weights = array_map(
            fn ($w) => is_numeric($w) && $w > 0 ? (float) $w : 0.0,
            array_slice(array_values($weights), 0, $count) + array_fill(0, $count, 0.0),
        );
        ksort($weights);

        $sum = array_sum($weights);

        // Nothing usable from the drafter — split as evenly as possible.
        if ($sum <= 0) {
            $weights = array_fill(0, $count, 1.0);
            $sum = $count;
        }

        $allocation = [];

        foreach ($weights as $weight) {
            $allocation[] = max(1, (int) round($weight / $sum * $this->quantity));
        }

        return $this->settleDrift($allocation);
    }

    /**
     * Rounding and the per-phase minimum of 1 both push the allocation off the
     * target, so nudge the largest (or smallest) phase until it lands exactly.
     */
    private function settleDrift(array $allocation): array
    {
        $guard = 0;

        while (array_sum($allocation) !== $this->quantity && $guard++ < 10000) {
            if (array_sum($allocation) > $this->quantity) {
                $index = array_search(max($allocation), $allocation, true);

                if ($allocation[$index] <= 1) {
                    break; // Every phase is already at the floor of 1.
                }

                $allocation[$index]--;

                continue;
            }

            $allocation[array_search(min($allocation), $allocation, true)]++;
        }

        return $allocation;
    }
}
