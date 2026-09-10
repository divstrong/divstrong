<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One click of "Find Prospects" — a search for creative and media teams who might need a
 * development bench, from the first web search through to the prospects that landed in the book.
 *
 * Also the progress bar. There is no queue worker on these installs (QUEUE_CONNECTION=sync),
 * so the work is advanced a step at a time by the list page polling; this row is the state that
 * survives between those polls.
 */
class ProspectDiscoveryRun extends Model
{
    /** Waiting for its first step. */
    public const STATUS_PENDING = 'pending';

    /** Asking the model for companies, a round at a time. */
    public const STATUS_DISCOVERING = 'discovering';

    /** Reading contact pages and checking the addresses found on them. */
    public const STATUS_VERIFYING = 'verifying';

    /** Writing the survivors into `prospects`. */
    public const STATUS_IMPORTING = 'importing';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses where the runner still has work to do — the page keeps polling on these. */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DISCOVERING,
        self::STATUS_VERIFYING,
        self::STATUS_IMPORTING,
    ];

    protected $fillable = [
        'requested_by',
        'assigned_to',
        'source',
        'target_count',
        'criteria',
        'status',
        'stage_message',
        'round',
        'companies_found',
        'emails_harvested',
        'accepted',
        'rejected',
        'imported',
        'tokens_in',
        'tokens_out',
        'searches',
        'error',
        'started_at',
        'finished_at',
    ];

    /**
     * Counters start at zero in memory, not just in the database.
     *
     * A column default only applies to the stored row — Eloquent hands back a model whose
     * unlisted attributes are null, so a freshly created run had a null round and null tallies
     * until something reloaded it. Everything here does arithmetic on these.
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'round' => 0,
        'companies_found' => 0,
        'emails_harvested' => 0,
        'accepted' => 0,
        'rejected' => 0,
        'imported' => 0,
        'tokens_in' => 0,
        'tokens_out' => 0,
        'searches' => 0,
    ];

    protected $casts = [
        'criteria' => 'array',
        'target_count' => 'integer',
        'round' => 'integer',
        'companies_found' => 'integer',
        'emails_harvested' => 'integer',
        'accepted' => 'integer',
        'rejected' => 'integer',
        'imported' => 'integer',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'searches' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(ProspectDiscoveryCandidate::class, 'run_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Rough completion, for the progress bar.
     *
     * Deliberately not a real percentage of work done — nobody knows how many pages will need
     * reading to find the next good address. It weights the stages so the bar always moves
     * forward and never sits at 99%, which is the only thing a progress bar owes anyone.
     */
    public function progress(): int
    {
        if ($this->status === self::STATUS_COMPLETE) {
            return 100;
        }

        if (in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED], true)) {
            return 100;
        }

        $rounds = max(1, (int) config('prospecting.max_rounds', 6));

        return match ($this->status) {
            self::STATUS_PENDING => 2,
            // Discovery owns the first 55%, spread across the rounds it is allowed.
            self::STATUS_DISCOVERING => (int) min(55, 5 + ($this->round / $rounds) * 50),
            // Verification owns 55–95%, measured against the target rather than the candidate
            // pool: the run stops early once enough addresses clear, so the target is the
            // honest denominator.
            self::STATUS_VERIFYING => (int) min(
                95,
                55 + (($this->accepted + $this->rejected) / max(1, $this->target_count * 2)) * 40,
            ),
            self::STATUS_IMPORTING => 97,
            default => 0,
        };
    }

    /** The most recent run, whatever its state — what the list page shows a banner for. */
    public static function latestFor(?int $userId): ?self
    {
        return static::query()
            ->when($userId, fn ($q) => $q->where('requested_by', $userId))
            ->latest('id')
            ->first();
    }
}
