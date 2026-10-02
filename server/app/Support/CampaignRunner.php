<?php

namespace App\Support;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignEnrollment;
use App\Models\CampaignStep;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Enrolling prospects and sending whatever is due.
 *
 * The guards are the important part of this class. Cold email is the one feature where
 * doing nothing is often correct, and each of these is a case where sending would be
 * actively harmful: mailing someone who opted out, chasing someone who already booked,
 * or writing again to an address that bounced.
 */
class CampaignRunner
{
    /**
     * Put a prospect into a campaign.
     *
     * Returns null when they cannot be enrolled — already walking this campaign, opted
     * out, no address, or (for a preview campaign) nothing built to show them yet.
     */
    public static function enroll(Campaign $campaign, Prospect $prospect, ?User $by = null, ?Carbon $startAt = null): ?CampaignEnrollment
    {
        if (! static::canEnroll($campaign, $prospect)) {
            return null;
        }

        $firstStep = $campaign->activeSteps()->first();

        if (! $firstStep) {
            return null;
        }

        $enrollment = CampaignEnrollment::create([
            'campaign_id' => $campaign->id,
            'prospect_id' => $prospect->id,
            'status' => CampaignEnrollment::STATUS_ACTIVE,
            'next_send_at' => ($startAt ?? now())->copy()->addDays($firstStep->delay_days),
            'enrolled_by' => $by?->id ?? auth()->id(),
        ]);

        ProspectActivity::record([
            'prospect_id' => $prospect->id,
            'type' => ProspectActivity::NOTE,
            'user_id' => $by?->id ?? auth()->id(),
            'description' => 'Enrolled in ' . $campaign->name,
            'meta' => ['campaign_id' => $campaign->id],
        ]);

        return $enrollment;
    }

    public static function canEnroll(Campaign $campaign, Prospect $prospect): bool
    {
        if (blank($prospect->email) || $prospect->isUnsubscribed()) {
            return false;
        }

        // A preview campaign with nothing to preview is four emails pointing at a dead
        // link — the single worst thing this feature could do.
        if (static::needsPreview($campaign) && ! $prospect->hasPreview()) {
            return false;
        }

        return ! $prospect->enrollments()
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', [CampaignEnrollment::STATUS_ACTIVE, CampaignEnrollment::STATUS_COMPLETED])
            ->exists();
    }

    /** Campaigns whose emails link to a prospect's own preview page. */
    public static function needsPreview(Campaign $campaign): bool
    {
        return $campaign->steps()
            ->where(fn ($q) => $q->where('template_key', 'like', 'promo_preview%')
                ->orWhere('template_key', 'like', 'general_preview%'))
            ->exists();
    }

    /**
     * Send everything due, up to a cap.
     *
     * @return array{sent: int, skipped: int, stopped: int}
     */
    public static function dispatchDue(int $limit = 50, ?Carbon $at = null): array
    {
        $counts = ['sent' => 0, 'skipped' => 0, 'stopped' => 0];

        $enrollments = CampaignEnrollment::due($at)
            ->with(['campaign.steps', 'prospect'])
            ->limit($limit)
            ->get();

        foreach ($enrollments as $enrollment) {
            $outcome = static::advance($enrollment);
            $counts[$outcome] = ($counts[$outcome] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Move one enrollment forward: send its next step, or stop it.
     *
     * @return 'sent'|'skipped'|'stopped'
     */
    public static function advance(CampaignEnrollment $enrollment): string
    {
        $prospect = $enrollment->prospect;
        $campaign = $enrollment->campaign;

        if (! $prospect || ! $campaign || ! $campaign->is_active) {
            return 'skipped';
        }

        if ($reason = static::stopReasonFor($enrollment, $prospect, $campaign)) {
            $enrollment->stop($reason);

            return 'stopped';
        }

        $step = $enrollment->nextStep();

        if (! $step) {
            $enrollment->complete();

            return 'stopped';
        }

        try {
            static::send($prospect, $step, $enrollment);
        } catch (\Throwable $e) {
            Log::error('Campaign send failed', [
                'enrollment' => $enrollment->id,
                'step' => $step->id,
                'error' => $e->getMessage(),
            ]);

            // Retried on the next tick rather than dropped: a transient SMTP failure
            // should not silently end somebody's sequence.
            $enrollment->forceFill(['next_send_at' => now()->addHours(1)])->save();

            return 'skipped';
        }

        $following = $campaign->stepAfter($step->position);

        $enrollment->forceFill([
            'last_step_position' => $step->position,
            'last_sent_at' => now(),
            'next_send_at' => $following ? now()->addDays($following->delay_days) : null,
            'status' => $following ? CampaignEnrollment::STATUS_ACTIVE : CampaignEnrollment::STATUS_COMPLETED,
            'completed_at' => $following ? null : now(),
        ])->save();

        return 'sent';
    }

    /** Why this enrollment should stop now, or null to carry on. */
    protected static function stopReasonFor(CampaignEnrollment $enrollment, Prospect $prospect, Campaign $campaign): ?string
    {
        if ($prospect->isUnsubscribed()) {
            return CampaignEnrollment::STOP_UNSUBSCRIBED;
        }

        if (blank($prospect->email)) {
            return CampaignEnrollment::STOP_NO_EMAIL;
        }

        if (static::needsPreview($campaign) && ! $prospect->hasPreview()) {
            return CampaignEnrollment::STOP_NO_PREVIEW;
        }

        // Writing again to an address whose last send hard-bounced is how a sending
        // domain gets throttled, and the message is not arriving anyway.
        if (Prospect::query()->whereKey($prospect->id)->currentlyBounced()->exists()) {
            return CampaignEnrollment::STOP_BOUNCED;
        }

        if ($campaign->stop_on_booking && $prospect->hasBookedMeeting()) {
            return CampaignEnrollment::STOP_BOOKED;
        }

        // An explicit "no" is an answer. Continuing to chase it is the behaviour that
        // makes people press the spam button.
        if ($prospect->interested === false) {
            return CampaignEnrollment::STOP_NOT_INTERESTED;
        }

        return null;
    }

    /**
     * Send one step by hand, now.
     *
     * Kept in step with the sequence rather than sitting beside it: if this prospect is
     * walking the campaign this step belongs to, the enrolment is moved to that position
     * and the following step rescheduled from today. Otherwise the same email would arrive
     * twice — once because somebody pressed the button, once because the scheduler still
     * believed it was owed.
     *
     * @param  array<int, string>|null  $emails  who to send to; defaults to the prospect's own address
     *
     * @throws \RuntimeException when the send would be unlawful or pointless
     */
    public static function sendStepNow(Prospect $prospect, CampaignStep $step, ?User $sender = null, ?array $emails = null): void
    {
        if ($prospect->isUnsubscribed()) {
            throw new \RuntimeException('This prospect has opted out of email.');
        }

        $emails = array_values(array_filter($emails ?? [$prospect->email]));

        if ($emails === []) {
            throw new \RuntimeException('This prospect has no email address.');
        }

        $campaign = $step->campaign;

        if ($campaign && static::needsPreview($campaign) && ! $prospect->hasPreview()) {
            throw new \RuntimeException('This email links to a preview page, and no preview URL is set on this prospect.');
        }

        ProspectMailer::send(
            $prospect,
            new CampaignMail($prospect, $step, $sender ?? auth()->user()),
            $emails,
            $step->activityLabel(),
        );

        $enrollment = $prospect->enrollments()
            ->where('campaign_id', $step->campaign_id)
            ->where('status', CampaignEnrollment::STATUS_ACTIVE)
            ->first();

        if (! $enrollment) {
            return;
        }

        $following = $campaign?->stepAfter($step->position);

        $enrollment->forceFill([
            'last_step_position' => $step->position,
            'last_sent_at' => now(),
            'next_send_at' => $following ? now()->addDays($following->delay_days) : null,
            'status' => $following ? CampaignEnrollment::STATUS_ACTIVE : CampaignEnrollment::STATUS_COMPLETED,
            'completed_at' => $following ? null : now(),
        ])->save();
    }

    /** Sends through ProspectMailer so the Postmark metadata and opt-out check apply. */
    protected static function send(Prospect $prospect, CampaignStep $step, CampaignEnrollment $enrollment): void
    {
        $sender = $enrollment->enroller ?? User::find($enrollment->enrolled_by);

        ProspectMailer::send(
            $prospect,
            new CampaignMail($prospect, $step, $sender),
            [$prospect->email],
            $step->activityLabel(),
        );
    }
}
