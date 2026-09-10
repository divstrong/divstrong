<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The engagement chips on the Prospects list, and the query scope behind their filter.
 *
 * These two have to agree. The chip is computed in PHP off the activity timeline; the filter
 * and the stat tile are computed in SQL. A prospect that shows a red bounce chip while the
 * Bounced filter cannot find it is worse than either being wrong on its own, because it makes
 * the list untrustworthy in a way nobody can pin down.
 */
class ProspectEngagementTest extends TestCase
{
    use RefreshDatabase;

    private function prospect(): Prospect
    {
        return Prospect::create([
            'name' => 'Dana Ruiz',
            'company' => 'Northbound Creative',
            'email' => 'dana@northbound.test',
            'segment' => Prospect::SEGMENT_AGENCY,
        ]);
    }

    private function event(Prospect $p, string $type, string $label, string $at): void
    {
        ProspectActivity::record([
            'prospect_id' => $p->id,
            'type' => $type,
            'meta' => ['label' => $label],
            'occurred_at' => $at,
        ]);
    }

    public function test_engagement_starts_at_none(): void
    {
        $this->assertSame(
            Prospect::ENGAGEMENT_NONE,
            $this->prospect()->emailEngagement('Agency Intro'),
        );
    }

    public function test_engagement_advances_through_the_states(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');
        $this->assertSame(Prospect::ENGAGEMENT_SENT, $p->fresh()->emailEngagement('Agency Intro'));

        $this->event($p, ProspectActivity::EMAIL_OPENED, 'Agency Intro', '2026-09-01 11:00:00');
        $this->assertSame(Prospect::ENGAGEMENT_OPENED, $p->fresh()->emailEngagement('Agency Intro'));

        $this->event($p, ProspectActivity::EMAIL_CLICKED, 'Agency Intro', '2026-09-01 12:00:00');
        $this->assertSame(Prospect::ENGAGEMENT_CLICKED, $p->fresh()->emailEngagement('Agency Intro'));
    }

    public function test_a_bounce_outranks_everything_else_in_the_same_attempt(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');
        $this->event($p, ProspectActivity::EMAIL_OPENED, 'Agency Intro', '2026-09-01 10:00:05');
        $this->event($p, ProspectActivity::EMAIL_BOUNCED, 'Agency Intro', '2026-09-01 10:00:10');

        $this->assertSame(Prospect::ENGAGEMENT_BOUNCED, $p->fresh()->emailEngagement('Agency Intro'));
    }

    /**
     * The regression this whole "latest attempt" design exists for: a re-send to a corrected
     * address has to clear the bounce, or the row stays red after the email demonstrably landed.
     */
    public function test_a_successful_resend_clears_an_earlier_bounce(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');
        $this->event($p, ProspectActivity::EMAIL_BOUNCED, 'Agency Intro', '2026-09-01 10:00:10');
        $this->assertTrue(Prospect::query()->currentlyBounced()->whereKey($p->id)->exists());

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-02 09:00:00');

        $this->assertSame(Prospect::ENGAGEMENT_SENT, $p->fresh()->emailEngagement('Agency Intro'));
        $this->assertFalse(
            Prospect::query()->currentlyBounced()->whereKey($p->id)->exists(),
            'The SQL scope behind the Bounced filter must agree with the chip.',
        );
    }

    public function test_emails_do_not_bleed_into_each_other(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');
        $this->event($p, ProspectActivity::EMAIL_CLICKED, 'Agency Intro', '2026-09-01 11:00:00');

        $this->assertSame(Prospect::ENGAGEMENT_NONE, $p->fresh()->emailEngagement('Client Intro'));
        $this->assertSame(Prospect::ENGAGEMENT_NONE, $p->fresh()->emailEngagement('General Update'));
    }

    /**
     * The list eager-loads a narrowed slice of the timeline so a page of chips costs no
     * queries. That optimisation is only safe if it produces identical answers.
     */
    public function test_the_eager_loaded_slice_gives_the_same_answers(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');
        $this->event($p, ProspectActivity::EMAIL_OPENED, 'Agency Intro', '2026-09-01 11:00:00');

        $lazy = Prospect::findOrFail($p->id);
        $eager = Prospect::with('checklistActivities')->findOrFail($p->id);

        $this->assertSame(
            $lazy->emailEngagement('Agency Intro'),
            $eager->emailEngagement('Agency Intro'),
        );
        $this->assertSame(
            $lazy->hasSentEmail('Agency Intro'),
            $eager->hasSentEmail('Agency Intro'),
        );
        $this->assertTrue($eager->relationLoaded('checklistActivities'));
    }

    public function test_has_sent_email_is_per_label(): void
    {
        $p = $this->prospect();

        $this->event($p, ProspectActivity::EMAIL_SENT, 'Agency Intro', '2026-09-01 10:00:00');

        $this->assertTrue($p->fresh()->hasSentEmail('Agency Intro'));
        $this->assertFalse($p->fresh()->hasSentEmail('Client Intro'));
    }

    public function test_segment_picks_the_matching_email(): void
    {
        $this->assertSame('Agency Intro', Prospect::segmentEmailLabel(Prospect::SEGMENT_AGENCY));
        $this->assertSame('Client Intro', Prospect::segmentEmailLabel(Prospect::SEGMENT_CLIENT));
        $this->assertSame('General Update', Prospect::segmentEmailLabel(Prospect::SEGMENT_GENERAL));
        // An unrecognised segment must still name a real email, not blow up at send time.
        $this->assertArrayHasKey(Prospect::segmentEmailLabel(null), Prospect::EMAIL_LABELS);
    }

    /** Every label the chips iterate must be one the code can actually send. */
    public function test_every_email_label_has_a_mailable(): void
    {
        $mailables = [
            'Agency Intro' => \App\Mail\AgencyIntro::class,
            'Client Intro' => \App\Mail\ClientIntro::class,
            'General Update' => \App\Mail\GeneralUpdate::class,
        ];

        $this->assertSame(array_keys(Prospect::EMAIL_LABELS), array_keys($mailables));
    }
}
