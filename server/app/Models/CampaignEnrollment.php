<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One prospect's walk through one campaign.
 *
 * This is the row the scheduler reads, so everything it needs to decide "send now or
 * not" lives here rather than being derived from the activity timeline on every tick.
 */
class CampaignEnrollment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_STOPPED = 'stopped';

    /** Why a walk ended early. Kept as data because "why did this stop" is the first
     *  question anyone asks of a campaign that under-delivered. */
    public const STOP_UNSUBSCRIBED = 'unsubscribed';

    public const STOP_BOUNCED = 'bounced';

    public const STOP_BOOKED = 'booked';

    public const STOP_REPLIED = 'replied';

    public const STOP_NOT_INTERESTED = 'not_interested';

    public const STOP_MANUAL = 'manual';

    public const STOP_NO_EMAIL = 'no_email';

    public const STOP_NO_PREVIEW = 'no_preview';

    protected $fillable = [
        'campaign_id', 'prospect_id', 'status', 'last_step_position', 'last_sent_at',
        'next_send_at', 'stop_reason', 'stopped_at', 'completed_at', 'enrolled_by',
    ];

    protected $casts = [
        'last_step_position' => 'integer',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
        'stopped_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function enroller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Everything due to go out, oldest first so a backlog drains in order. */
    public function scopeDue(Builder $query, ?\DateTimeInterface $at = null): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', $at ?? now())
            ->orderBy('next_send_at');
    }

    /** The step this enrollment is waiting to send. */
    public function nextStep(): ?CampaignStep
    {
        return $this->campaign?->stepAfter($this->last_step_position);
    }

    public function stop(string $reason): void
    {
        if (! $this->isActive()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_STOPPED,
            'stop_reason' => $reason,
            'stopped_at' => now(),
            'next_send_at' => null,
        ])->save();
    }

    public function complete(): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'next_send_at' => null,
        ])->save();
    }

    /** @return array<string, string> */
    public static function stopReasons(): array
    {
        return [
            self::STOP_UNSUBSCRIBED => 'Unsubscribed',
            self::STOP_BOUNCED => 'Bounced',
            self::STOP_BOOKED => 'Booked a call',
            self::STOP_REPLIED => 'Replied',
            self::STOP_NOT_INTERESTED => 'Said no',
            self::STOP_MANUAL => 'Stopped by hand',
            self::STOP_NO_EMAIL => 'No email address',
            self::STOP_NO_PREVIEW => 'No preview built',
        ];
    }
}
