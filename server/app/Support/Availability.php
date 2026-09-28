<?php

namespace App\Support;

use App\Models\Meeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns the rules in config/scheduling.php into the list of slots a visitor may book.
 *
 * Slots are computed on demand and never stored. Everything is reasoned about in UTC and
 * only formatted into a timezone at the edge — the daylight-saving bugs in booking systems
 * all come from comparing wall-clock times that mean different things on different days.
 */
class Availability
{
    /** @return array<string, mixed> */
    public static function config(): array
    {
        return config('scheduling');
    }

    public static function hostTimezone(): string
    {
        return static::config()['timezone'] ?? 'America/New_York';
    }

    public static function durationMinutes(): int
    {
        return (int) (static::config()['duration_minutes'] ?? 30);
    }

    /**
     * The days that have at least one free slot, as Y-m-d in the host timezone.
     *
     * @return Collection<int, array{date: string, label: string, day: string, slots: int}>
     */
    public static function days(?string $viewerTimezone = null): Collection
    {
        $tz = static::hostTimezone();
        $maxDays = (int) (static::config()['max_days_ahead'] ?? 21);

        $days = collect();

        for ($offset = 0; $offset <= $maxDays; $offset++) {
            $date = Carbon::now($tz)->startOfDay()->addDays($offset);
            $slots = static::slotsOn($date->format('Y-m-d'));

            if ($slots->isEmpty()) {
                continue;
            }

            $days->push([
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
                'day' => $date->format('D'),
                'slots' => $slots->count(),
            ]);
        }

        return $days;
    }

    /**
     * Free slots on one date, as UTC start times.
     *
     * @return Collection<int, Carbon>
     */
    public static function slotsOn(string $date): Collection
    {
        $config = static::config();
        $tz = static::hostTimezone();

        if (in_array($date, array_map('trim', $config['blackout_dates'] ?? []), true)) {
            return collect();
        }

        try {
            $day = Carbon::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
        } catch (\Throwable) {
            return collect();
        }

        // Past days and anything beyond the booking horizon are not offered, whatever
        // the URL says — this method is reachable from user input.
        $today = Carbon::now($tz)->startOfDay();
        if ($day->lt($today) || $day->gt($today->copy()->addDays((int) ($config['max_days_ahead'] ?? 21)))) {
            return collect();
        }

        $windows = $config['hours'][strtolower($day->format('l'))] ?? [];

        if ($windows === []) {
            return collect();
        }

        $duration = static::durationMinutes();
        $step = $duration + (int) ($config['buffer_minutes'] ?? 0);
        $earliest = Carbon::now('UTC')->addHours((int) ($config['min_notice_hours'] ?? 0));
        $taken = static::takenOn($day);

        $slots = collect();

        foreach ($windows as $window) {
            [$from, $to] = $window;

            $cursor = static::atTime($day, $from);
            $end = static::atTime($day, $to);

            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $startUtc = $cursor->copy()->utc();
                $endUtc = $startUtc->copy()->addMinutes($duration);

                $free = $startUtc->gte($earliest) && ! $taken->contains(
                    // Overlap, not equality: the duration or the windows may have changed
                    // since an existing booking was made, so a slot that merely touches a
                    // booked call has to be withheld too.
                    fn (array $booked) => $startUtc->lt($booked['ends_at']) && $endUtc->gt($booked['starts_at'])
                );

                if ($free) {
                    $slots->push($startUtc);
                }

                $cursor->addMinutes($step);
            }
        }

        return $slots->sort()->values();
    }

    /** Is this exact UTC start still bookable? The check the booking itself runs. */
    public static function isBookable(Carbon $startUtc): bool
    {
        $date = $startUtc->copy()->setTimezone(static::hostTimezone())->format('Y-m-d');

        return static::slotsOn($date)->contains(fn (Carbon $slot) => $slot->equalTo($startUtc));
    }

    /**
     * Bookings that overlap a given day, with a buffer's grace either side.
     *
     * @return Collection<int, array{starts_at: Carbon, ends_at: Carbon}>
     */
    protected static function takenOn(Carbon $day): Collection
    {
        $buffer = (int) (static::config()['buffer_minutes'] ?? 0);

        return Meeting::query()
            ->booked()
            ->whereBetween('starts_at', [
                $day->copy()->utc()->subDay(),
                $day->copy()->addDay()->utc()->addDay(),
            ])
            ->get(['starts_at', 'ends_at'])
            ->map(fn (Meeting $meeting) => [
                'starts_at' => $meeting->starts_at->copy()->subMinutes($buffer),
                'ends_at' => $meeting->ends_at->copy()->addMinutes($buffer),
            ]);
    }

    /** A 'HH:MM' on a given day, in the host timezone. */
    protected static function atTime(Carbon $day, string $time): Carbon
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return $day->copy()->setTime((int) $hour, (int) $minute);
    }

    /**
     * Timezones offered on the booking page: the common US ones plus whatever the
     * browser reported, so a visitor abroad still sees their own clock.
     *
     * @return array<string, string>
     */
    public static function timezoneOptions(?string $detected = null): array
    {
        $options = [
            'America/New_York' => 'Eastern',
            'America/Chicago' => 'Central',
            'America/Denver' => 'Mountain',
            'America/Phoenix' => 'Arizona',
            'America/Los_Angeles' => 'Pacific',
        ];

        if (filled($detected) && ! array_key_exists($detected, $options) && in_array($detected, timezone_identifiers_list(), true)) {
            $options = [$detected => str_replace('_', ' ', $detected)] + $options;
        }

        return $options;
    }
}
