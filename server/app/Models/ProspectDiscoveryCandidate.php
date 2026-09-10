<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One company found by a discovery run, and what became of it.
 *
 * Rejects are kept, not deleted. "50 found, 12 imported" is only actionable if you can see that
 * 20 had no published address, 9 were info@ boxes and 6 failed their mailbox check — that is the
 * difference between "the feature is broken" and "this niche does not publish named emails".
 */
class ProspectDiscoveryCandidate extends Model
{
    /** Found by the model; no address read off a real page yet. */
    public const STATUS_PENDING = 'pending';

    /** An address was harvested and is waiting on the verification gates. */
    public const STATUS_HARVESTED = 'harvested';

    /** Cleared every gate. Waiting to be written into the book. */
    public const STATUS_ACCEPTED = 'accepted';

    /** Written into `prospects`. */
    public const STATUS_IMPORTED = 'imported';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'run_id',
        'company',
        'contact_name',
        'title',
        'email',
        'phone',
        'website',
        'city',
        'state',
        'source_url',
        'status',
        'reject_reason',
        'checks',
        'prospect_id',
    ];

    protected $casts = [
        'checks' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ProspectDiscoveryRun::class, 'run_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function reject(string $reason, array $checks = []): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reject_reason' => $reason,
            'checks' => array_merge($this->checks ?? [], $checks),
        ]);
    }
}
