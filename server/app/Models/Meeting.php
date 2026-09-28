<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A booked call. Times are UTC; `timezone` is the one the booker picked in, kept so
 * every message back to them reads in their own clock.
 */
class Meeting extends Model
{
    public const STATUS_BOOKED = 'booked';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'prospect_id', 'token', 'name', 'email', 'company', 'phone', 'notes',
        'starts_at', 'ends_at', 'timezone', 'status', 'canceled_at', 'canceled_by', 'source',
    ];

    protected $casts = [
        'canceled_at' => 'datetime',
    ];

    /**
     * starts_at / ends_at are stored in UTC and read back in UTC, explicitly.
     *
     * A plain 'datetime' cast would parse them in config('app.timezone') — America/New_York
     * here — so a slot written as 13:00 UTC came back as 13:00 Eastern and every time the
     * app displayed was four hours out. These two accessors are the whole fix, and the
     * reason nothing else in this feature has to think about it.
     */
    protected function startsAt(): Attribute
    {
        return static::utcAttribute();
    }

    protected function endsAt(): Attribute
    {
        return static::utcAttribute();
    }

    protected static function utcAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value, 'UTC'),
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->utc()->format('Y-m-d H:i:s'),
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $meeting) {
            $meeting->token ??= Str::lower(Str::random(24));
        });
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->booked()->where('starts_at', '>=', now()->utc())->orderBy('starts_at');
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isPast(): bool
    {
        return $this->ends_at->isPast();
    }

    /** The start in the booker's own timezone, for anything they will read. */
    public function startsAtLocal(): Carbon
    {
        return $this->starts_at->copy()->setTimezone($this->timezone);
    }

    public function endsAtLocal(): Carbon
    {
        return $this->ends_at->copy()->setTimezone($this->timezone);
    }

    /** e.g. "Thursday, October 2 · 10:00 – 10:30 AM (EDT)" */
    public function readableTime(?string $timezone = null): string
    {
        $tz = $timezone ?: $this->timezone;
        $start = $this->starts_at->copy()->setTimezone($tz);
        $end = $this->ends_at->copy()->setTimezone($tz);

        return $start->format('l, F j') . ' · ' . $start->format('g:i') . ' – ' . $end->format('g:i A')
            . ' (' . $start->format('T') . ')';
    }

    public function cancelUrl(): string
    {
        return url('/meet/' . $this->token);
    }
}
