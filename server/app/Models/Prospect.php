<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A creative or media team divStrong could augment — an agency, a studio, an SEO
 * or media firm — plus everything we have ever sent them and what came back.
 */
class Prospect extends Model
{
    /**
     * The three outreach emails, keyed by the ProspectMailer label that tags their
     * activity, mapped to the short name the engagement chips use.
     *
     * One source of truth: the list filter, the chips, the stat tiles and anything
     * else counting emails all read this, so adding a fourth email means editing
     * one array rather than hunting for hardcoded label strings.
     */
    public const EMAIL_LABELS = [
        'Agency Intro' => 'Agency',
        'Client Intro' => 'Client',
        'General Update' => 'General',
    ];

    /**
     * Who we are writing to, and therefore which story we tell.
     *
     * AGENCY   creative/media shops with no deep engineering bench — the white-label pitch
     * CLIENT   companies that would buy delivery directly — the agile, AI-enabled pitch
     * GENERAL  anyone on the list — news, events, offers, whatever the topic is
     *
     * Advisory rather than enforced: all three emails stay available on every row,
     * because a studio that also buys direct is a real thing and the rep who just
     * had the phone call knows which conversation it was.
     */
    public const SEGMENT_AGENCY = 'agency';

    public const SEGMENT_CLIENT = 'client';

    public const SEGMENT_GENERAL = 'general';

    /** @return array<string, string> value => label */
    public static function segments(): array
    {
        return [
            self::SEGMENT_AGENCY => 'Agency',
            self::SEGMENT_CLIENT => 'Client',
            self::SEGMENT_GENERAL => 'General',
        ];
    }

    /** The email that fits a segment, for the row's default send. */
    public static function segmentEmailLabel(?string $segment): string
    {
        return match ($segment) {
            self::SEGMENT_CLIENT => 'Client Intro',
            self::SEGMENT_GENERAL => 'General Update',
            default => 'Agency Intro',
        };
    }

    /**
     * Lead triage — separate from `status`, which tracks pipeline position. This
     * answers the question that comes first: is this worth working at all?
     *
     * Unqualified is the default because an imported prospect has not been assessed.
     */
    public const LEAD_UNQUALIFIED = 'unqualified';

    public const LEAD_QUALIFIED = 'qualified';

    public const LEAD_DISMISSED = 'dismissed';

    /** @return array<string, string> value => label, in triage order */
    public static function leadStatuses(): array
    {
        return [
            self::LEAD_QUALIFIED => 'Qualified',
            self::LEAD_UNQUALIFIED => 'Unqualified',
            self::LEAD_DISMISSED => 'Dismissed',
        ];
    }

    /** @return array<string, string> pipeline position */
    public static function statuses(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'negotiation' => 'Negotiating',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }

    /** Engagement states for an email, in the order a recipient reaches them. */
    public const ENGAGEMENT_NONE = 'none';

    public const ENGAGEMENT_SENT = 'sent';

    public const ENGAGEMENT_OPENED = 'opened';

    public const ENGAGEMENT_CLICKED = 'clicked';

    public const ENGAGEMENT_BOUNCED = 'bounced';

    /** Activity types the list table's chips and action-hiding logic read. */
    public const CHECKLIST_TYPES = [
        ProspectActivity::EMAIL_SENT,
        ProspectActivity::EMAIL_OPENED,
        ProspectActivity::EMAIL_CLICKED,
        ProspectActivity::EMAIL_BOUNCED,
    ];

    /** How an opt-out reached us. A complaint is a deliverability incident; a click is not. */
    public const UNSUB_LINK = 'link';

    public const UNSUB_ONE_CLICK = 'one-click';

    public const UNSUB_COMPLAINT = 'complaint';

    public const UNSUB_MANUAL = 'manual';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'title',
        'website',
        'preview_url',
        'preview_token',
        'preview_image',
        'preview_viewed_at',
        'likes_design',
        'interested',
        'fit_rating',
        'responded_at',
        'preview_comments',
        'segment',
        'unsubscribed_at',
        'unsubscribe_source',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'status',
        'lead_status',
        'called_at',
        'priority',
        'notes',
        'source',
        'assigned_to',
        'client_id',
        'converted_at',
        'converted_by',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'preview_viewed_at' => 'datetime',
        'responded_at' => 'datetime',
        'likes_design' => 'boolean',
        'interested' => 'boolean',
        'fit_rating' => 'integer',
        'called_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'priority' => 'integer',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProspectActivity::class, 'prospect_id')->latest('occurred_at');
    }

    /**
     * Prospects whose most recent send is the one that bounced.
     *
     * The chips read the latest attempt per email (see latestAttempt()). This is the
     * SQL counterpart for the stat tile and the list filter, so a prospect re-sent
     * successfully after a bounce stops being counted as bounced everywhere at once.
     *
     * Per prospect rather than per email label: a bounce follows its own send within
     * seconds, so "latest bounce is not older than latest send" means the current
     * send is the one that failed. NULL on either side compares false, which is the
     * right answer for never-bounced and never-sent alike.
     */
    public function scopeCurrentlyBounced(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query->whereRaw(
            "(select max(b.occurred_at) from prospect_activities b where b.prospect_id = {$table}.id and b.type = ?)"
            .' >= '
            ."(select max(s.occurred_at) from prospect_activities s where s.prospect_id = {$table}.id and s.type = ?)",
            [ProspectActivity::EMAIL_BOUNCED, ProspectActivity::EMAIL_SENT],
        );
    }

    public function isConverted(): bool
    {
        return $this->client_id !== null;
    }

    /**
     * Has this person asked us to stop?
     *
     * Checked by every path that sends commercial mail. Nothing here is advisory —
     * an unsubscribe we accepted and then mailed through is a broken legal promise
     * with a timestamp attached proving we knew.
     */
    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    /**
     * Record an opt-out and timeline it.
     *
     * Idempotent: a second request keeps the original timestamp, because the date we
     * were first told is the date that matters if anyone ever asks.
     */
    public function unsubscribe(string $source = self::UNSUB_LINK, ?string $note = null): void
    {
        if ($this->isUnsubscribed()) {
            return;
        }

        $this->forceFill([
            'unsubscribed_at' => now(),
            'unsubscribe_source' => $source,
        ])->save();

        ProspectActivity::record([
            'prospect_id' => $this->id,
            'type' => ProspectActivity::UNSUBSCRIBED,
            'description' => $note ?? match ($source) {
                self::UNSUB_COMPLAINT => 'Marked this as spam',
                self::UNSUB_ONE_CLICK => 'Unsubscribed via their mail client',
                self::UNSUB_MANUAL => 'Unsubscribed by hand',
                default => 'Unsubscribed via the link in an email',
            },
            'meta' => ['source' => $source],
        ]);
    }

    /** Put someone back on the list. Only ever from the panel, never automatically. */
    public function resubscribe(): void
    {
        if (! $this->isUnsubscribed()) {
            return;
        }

        $this->forceFill(['unsubscribed_at' => null, 'unsubscribe_source' => null])->save();

        ProspectActivity::record([
            'prospect_id' => $this->id,
            'type' => ProspectActivity::NOTE,
            'user_id' => auth()->id(),
            'description' => 'Opt-out cleared — outreach re-enabled',
        ]);
    }

    /** Prospects it is still lawful to send commercial mail to. */
    public function scopeMailable(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    /**
     * The slice of the timeline the list table reads: which emails have gone out and
     * how far each got.
     *
     * It exists purely so the table can eager-load it. Deciding chip state and action
     * visibility per row cost four `exists()` queries per prospect — 200 round trips
     * to render one page — which is invisible locally and very visible over a network.
     * Named separately from `activities()` so an unrelated eager load can never
     * silently narrow what these read.
     */
    public function checklistActivities(): HasMany
    {
        return $this->hasMany(ProspectActivity::class, 'prospect_id')
            ->select('id', 'prospect_id', 'type', 'meta', 'occurred_at')
            ->whereIn('type', self::CHECKLIST_TYPES);
    }

    /**
     * Whether an email (identified by its ProspectMailer label) has already gone to
     * this prospect. Drives the checklist-style hiding of completed row actions.
     */
    public function hasSentEmail(string $label): bool
    {
        if ($this->relationLoaded('checklistActivities')) {
            return $this->checklistActivities->contains(
                fn (ProspectActivity $activity) => $activity->type === ProspectActivity::EMAIL_SENT
                    && ($activity->meta['label'] ?? null) === $label,
            );
        }

        return $this->activities()
            ->where('type', ProspectActivity::EMAIL_SENT)
            ->where('meta->label', $label)
            ->exists();
    }

    /** The activity slice the chips read from, loaded or fetched. */
    protected function checklistSlice(): \Illuminate\Support\Collection
    {
        return $this->relationLoaded('checklistActivities')
            ? $this->checklistActivities
            : $this->activities()->whereIn('type', self::CHECKLIST_TYPES)->get();
    }

    /**
     * The events belonging to the most recent send of a given email.
     *
     * A prospect's history for one label can span several attempts: a send that hard
     * bounced on a bad address, then a re-send to a corrected one. Reading every event
     * across all attempts lets yesterday's bounce outrank today's successful delivery —
     * the chip stays red after the email has demonstrably landed.
     *
     * So the latest EMAIL_SENT for the label defines the current attempt, and only
     * events at or after it count. Correlating by message id would be cleaner, but over
     * SMTP the id stored on the send is an MTA queue id and the id on the webhook is
     * Postmark's GUID — they never match. Time is the reliable key; a bounce follows
     * its own send within seconds.
     *
     * @return \Illuminate\Support\Collection<int, ProspectActivity>
     */
    protected function latestAttempt(string $label): \Illuminate\Support\Collection
    {
        $forLabel = $this->checklistSlice()->filter(
            fn (ProspectActivity $activity) => ($activity->meta['label'] ?? null) === $label,
        );

        $latestSend = $forLabel
            ->where('type', ProspectActivity::EMAIL_SENT)
            ->sortByDesc('occurred_at')
            ->first();

        if (! $latestSend) {
            // Never sent, but there may be stray events. Show what we have rather
            // than hiding it.
            return $forLabel;
        }

        return $forLabel->filter(
            fn (ProspectActivity $activity) => $activity->occurred_at >= $latestSend->occurred_at,
        );
    }

    /**
     * How far the most recent send of a given email got: not sent, sent, opened,
     * clicked — or bounced, which outranks the rest because it means nothing arrived.
     *
     * Reads the eager-loaded slice when the table has primed it, so a page of chips
     * costs no queries. See checklistActivities() for why that matters.
     */
    public function emailEngagement(string $label): string
    {
        $attempt = $this->latestAttempt($label);

        if ($attempt->isEmpty()) {
            return self::ENGAGEMENT_NONE;
        }

        $types = $attempt->pluck('type')->all();

        return match (true) {
            in_array(ProspectActivity::EMAIL_BOUNCED, $types, true) => self::ENGAGEMENT_BOUNCED,
            in_array(ProspectActivity::EMAIL_CLICKED, $types, true) => self::ENGAGEMENT_CLICKED,
            in_array(ProspectActivity::EMAIL_OPENED, $types, true) => self::ENGAGEMENT_OPENED,
            in_array(ProspectActivity::EMAIL_SENT, $types, true) => self::ENGAGEMENT_SENT,
            default => self::ENGAGEMENT_NONE,
        };
    }

    /** When the furthest-reached event of the current attempt happened, for the tooltip. */
    public function emailEngagementAt(string $label, string $state): ?\Illuminate\Support\Carbon
    {
        $type = match ($state) {
            self::ENGAGEMENT_BOUNCED => ProspectActivity::EMAIL_BOUNCED,
            self::ENGAGEMENT_CLICKED => ProspectActivity::EMAIL_CLICKED,
            self::ENGAGEMENT_OPENED => ProspectActivity::EMAIL_OPENED,
            self::ENGAGEMENT_SENT => ProspectActivity::EMAIL_SENT,
            default => null,
        };

        if (! $type) {
            return null;
        }

        return $this->latestAttempt($label)
            ->where('type', $type)
            ->max('occurred_at');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CampaignEnrollment::class, 'prospect_id');
    }

    /** The live campaign walk, if there is one. */
    public function activeEnrollment(): ?CampaignEnrollment
    {
        return $this->enrollments()
            ->where('status', CampaignEnrollment::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'prospect_id');
    }

    public function hasBookedMeeting(): bool
    {
        return $this->meetings()->where('status', Meeting::STATUS_BOOKED)->exists();
    }

    /**
     * The token the preview page is addressed by, minted on first use.
     *
     * Lazily rather than on create: most prospects never get a design built, and a
     * column full of tokens for pages that will never exist is just surface area.
     */
    public function previewToken(): string
    {
        if (blank($this->preview_token)) {
            // Six hex characters (16.7M) is plenty for a page nobody can do harm with,
            // and short enough to read out over the phone; just don't reuse one.
            do {
                $token = bin2hex(random_bytes(3));
            } while (static::where('preview_token', $token)->exists());

            $this->forceFill(['preview_token' => $token])->save();
        }

        return $this->preview_token;
    }

    /** The tracked landing page — what the campaign emails actually link to. */
    public function previewLandingUrl(): ?string
    {
        if (blank($this->preview_url)) {
            return null;
        }

        return url('/p/' . $this->previewToken());
    }

    /** A prospect can only be enrolled in the preview campaign once there is a preview. */
    public function hasPreview(): bool
    {
        return filled($this->preview_url);
    }

    public function firstName(): string
    {
        $name = trim((string) $this->name);

        return $name === '' ? ($this->company ?: 'there') : (\Illuminate\Support\Str::before($name, ' ') ?: $name);
    }

    /**
     * What they wrote after saying no.
     *
     * Appended rather than overwritten: somebody who disliked the design AND explained why
     * they would not move has told you two different things, and the second should not
     * erase the first. Each entry is stamped with the question it answered so the pair
     * still reads as a conversation months later.
     */
    public function recordPreviewComment(string $question, string $comment): void
    {
        $comment = trim($comment);

        if ($comment === '') {
            return;
        }

        $heading = $question === 'interested' ? 'On switching' : 'On the design';
        $entry = $heading . ' (' . now()->format('M j, Y') . '): ' . $comment;

        $this->forceFill([
            'preview_comments' => trim(($this->preview_comments ? $this->preview_comments . "

" : '') . $entry),
        ])->save();

        ProspectActivity::record([
            'prospect_id' => $this->id,
            'type' => ProspectActivity::PREVIEW_COMMENT,
            'description' => $heading . ': ' . \Illuminate\Support\Str::limit($comment, 120),
            'meta' => ['question' => $question, 'comment' => $comment],
        ]);
    }

    /**
     * Record one of the two preview answers.
     *
     * Both the column and the timeline get written: the column is what the list filters
     * and the campaign guards read, the activity is what tells you when they said it.
     */
    public function recordPreviewAnswer(string $question, bool $answer): void
    {
        $column = $question === 'interested' ? 'interested' : 'likes_design';

        $this->forceFill([$column => $answer, 'responded_at' => now()])->save();

        ProspectActivity::record([
            'prospect_id' => $this->id,
            'type' => $question === 'interested'
                ? ProspectActivity::PREVIEW_INTEREST
                : ProspectActivity::PREVIEW_FEEDBACK,
            'description' => $question === 'interested'
                ? ($answer ? 'Interested in switching' : 'Not interested in switching')
                : ($answer ? 'Likes the design' : 'Does not like the design'),
            'meta' => ['question' => $question, 'answer' => $answer],
        ]);
    }

    /**
     * The third question: how well the concept fits them, 1–5.
     *
     * They can change their mind, so a re-rate overwrites the column but still timelines —
     * a 2 that becomes a 4 after a second look is worth knowing about.
     */
    public function recordFitRating(int $rating): void
    {
        $rating = max(1, min(5, $rating));

        if ($this->fit_rating === $rating) {
            return;
        }

        $this->forceFill(['fit_rating' => $rating, 'responded_at' => now()])->save();

        ProspectActivity::record([
            'prospect_id' => $this->id,
            'type' => ProspectActivity::PREVIEW_RATING,
            'description' => "Rated the fit {$rating}/5",
            'meta' => ['question' => 'fit', 'rating' => $rating],
        ]);
    }
}
