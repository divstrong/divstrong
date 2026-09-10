<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectActivity extends Model
{
    public const EMAIL_SENT = 'email_sent';
    public const EMAIL_OPENED = 'email_opened';
    public const EMAIL_CLICKED = 'email_clicked';
    public const EMAIL_BOUNCED = 'email_bounced';
    public const NOTE = 'note';
    public const STATUS_CHANGE = 'status_change';
    public const CONVERTED = 'converted';

    /** They asked us to stop emailing them, or told their provider we were spam. */
    public const UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'prospect_id',
        'type',
        'user_id',
        'description',
        'meta',
        'external_id',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record an activity, tolerating the table not existing yet so email sends never fail
     * just because the migration has not been run.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function record(array $attributes): ?self
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('prospect_activities')) {
            return null;
        }

        $attributes['occurred_at'] ??= now();

        return static::create($attributes);
    }

    /**
     * Presentation metadata (icon + color) for the timeline, keyed by type.
     *
     * @return array{icon: string, color: string, label: string}
     */
    public function presentation(): array
    {
        return match ($this->type) {
            self::EMAIL_SENT => ['icon' => 'heroicon-o-paper-airplane', 'color' => 'gray', 'label' => 'Email sent'],
            self::EMAIL_OPENED => ['icon' => 'heroicon-o-envelope-open', 'color' => 'info', 'label' => 'Email opened'],
            self::EMAIL_CLICKED => ['icon' => 'heroicon-o-cursor-arrow-rays', 'color' => 'success', 'label' => 'Link clicked'],
            self::EMAIL_BOUNCED => ['icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger', 'label' => 'Email bounced'],
            self::STATUS_CHANGE => ['icon' => 'heroicon-o-flag', 'color' => 'warning', 'label' => 'Status change'],
            self::CONVERTED => ['icon' => 'heroicon-o-check-badge', 'color' => 'success', 'label' => 'Converted'],
            self::UNSUBSCRIBED => ['icon' => 'heroicon-o-no-symbol', 'color' => 'danger', 'label' => 'Unsubscribed'],
            default => ['icon' => 'heroicon-o-chat-bubble-left-ellipsis', 'color' => 'gray', 'label' => 'Note'],
        };
    }
}
