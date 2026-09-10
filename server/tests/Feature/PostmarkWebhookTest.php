<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Support\ProspectMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The webhook that turns "sent" into "read".
 *
 * Everything here arrives from outside, so the interesting cases are the hostile and the
 * stale ones: an unauthenticated post, an event naming a prospect that no longer exists, an
 * event for mail this app never sent. All three have to be shrugged off with a 2xx, because
 * Postmark reads a non-2xx as a delivery failure and retries the same doomed event on a
 * schedule — one bad id otherwise becomes a permanent stream of 500s.
 */
class PostmarkWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.postmark.webhook_secret' => self::SECRET]);
    }

    private function prospect(): Prospect
    {
        return Prospect::create([
            'name' => 'Dana Ruiz',
            'company' => 'Northbound Creative',
            'email' => 'dana@northbound.test',
        ]);
    }

    private function hook(array $payload, ?string $token = self::SECRET)
    {
        return $this->postJson(
            '/api/webhooks/postmark'.($token === null ? '' : '?token='.$token),
            $payload,
        );
    }

    private function meta(Prospect $p, string $label = 'Agency Intro'): array
    {
        return [
            ProspectMailer::META_PROSPECT_ID => (string) $p->id,
            ProspectMailer::META_LABEL => $label,
        ];
    }

    public function test_it_rejects_a_post_with_no_secret(): void
    {
        $this->hook(['RecordType' => 'Open'], null)->assertUnauthorized();
    }

    public function test_it_rejects_a_post_with_the_wrong_secret(): void
    {
        $this->hook(['RecordType' => 'Open'], 'not-the-secret')->assertUnauthorized();
    }

    /** Unset is not "allow everything" — it must refuse rather than accept anonymous posts. */
    public function test_it_refuses_everything_when_no_secret_is_configured(): void
    {
        config(['services.postmark.webhook_secret' => null]);

        $this->hook(['RecordType' => 'Open'], null)->assertUnauthorized();
    }

    public function test_an_open_lands_on_the_timeline(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'Open',
            'MessageID' => 'abc',
            'Metadata' => $this->meta($p),
            'Recipient' => $p->email,
        ])->assertOk();

        $this->assertTrue(
            $p->activities()->where('type', ProspectActivity::EMAIL_OPENED)->exists(),
        );
        $this->assertSame(Prospect::ENGAGEMENT_OPENED, $p->fresh()->emailEngagement('Agency Intro'));
    }

    public function test_a_click_captures_the_link(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'Click',
            'MessageID' => 'abc',
            'Metadata' => $this->meta($p),
            'OriginalLink' => 'https://divstrong.com/contact',
        ])->assertOk();

        $click = $p->activities()->where('type', ProspectActivity::EMAIL_CLICKED)->firstOrFail();

        $this->assertSame('https://divstrong.com/contact', $click->meta['url']);
        $this->assertSame(Prospect::ENGAGEMENT_CLICKED, $p->fresh()->emailEngagement('Agency Intro'));
    }

    public function test_a_bounce_is_recorded_with_its_reason(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'Bounce',
            'MessageID' => 'abc',
            'Metadata' => $this->meta($p),
            'Type' => 'HardBounce',
            'Email' => $p->email,
        ])->assertOk();

        $bounce = $p->activities()->where('type', ProspectActivity::EMAIL_BOUNCED)->firstOrFail();

        $this->assertSame('HardBounce', $bounce->meta['bounce_type']);
        $this->assertSame(Prospect::ENGAGEMENT_BOUNCED, $p->fresh()->emailEngagement('Agency Intro'));
    }

    /**
     * A complaint is an opt-out that arrived the expensive way. Continuing to mail somebody
     * who pressed "this is spam" is the fastest route to a blocked sending domain, so this
     * suppresses immediately rather than only timelining the event.
     */
    public function test_a_spam_complaint_suppresses_the_prospect_immediately(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'SpamComplaint',
            'MessageID' => 'abc',
            'Metadata' => $this->meta($p),
            'Email' => $p->email,
        ])->assertOk();

        $this->assertTrue($p->fresh()->isUnsubscribed());
        $this->assertSame(Prospect::UNSUB_COMPLAINT, $p->fresh()->unsubscribe_source);
        $this->assertFalse(Prospect::query()->mailable()->whereKey($p->id)->exists());
    }

    /** Mail sent months ago to somebody since deleted. Must not 500 into a retry loop. */
    public function test_an_event_for_a_deleted_prospect_is_ignored(): void
    {
        $this->hook([
            'RecordType' => 'Open',
            'MessageID' => 'abc',
            'Metadata' => [ProspectMailer::META_PROSPECT_ID => '999999'],
        ])->assertOk()->assertJson(['status' => 'ignored']);
    }

    /** An order confirmation or proposal email sharing the same Postmark account. */
    public function test_an_event_for_unrelated_mail_is_ignored(): void
    {
        $this->hook(['RecordType' => 'Open', 'MessageID' => 'not-ours'])
            ->assertOk()
            ->assertJson(['status' => 'ignored']);
    }

    /** Deliveries are acknowledged but deliberately not timelined — they would say nothing. */
    public function test_a_delivery_event_is_acknowledged_without_a_timeline_entry(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'Delivery',
            'MessageID' => 'abc',
            'Metadata' => $this->meta($p),
        ])->assertOk();

        $this->assertSame(0, $p->activities()->count());
    }

    /**
     * Correlation falls back to the message id for mail sent before metadata existed.
     */
    public function test_it_falls_back_to_matching_the_original_send_by_message_id(): void
    {
        $p = $this->prospect();

        ProspectActivity::record([
            'prospect_id' => $p->id,
            'type' => ProspectActivity::EMAIL_SENT,
            'meta' => ['label' => 'Client Intro'],
            'external_id' => 'legacy-message-id',
        ]);

        $this->hook([
            'RecordType' => 'Open',
            'MessageID' => 'legacy-message-id',
        ])->assertOk();

        $open = $p->activities()->where('type', ProspectActivity::EMAIL_OPENED)->firstOrFail();

        $this->assertSame('Client Intro', $open->meta['label'], 'the label comes off the original send');
    }

    /** Postmark does not commit to the casing of metadata keys. */
    public function test_metadata_keys_are_matched_case_insensitively(): void
    {
        $p = $this->prospect();

        $this->hook([
            'RecordType' => 'Open',
            'MessageID' => 'abc',
            'Metadata' => ['Prospect-Id' => (string) $p->id, 'Label' => 'Agency Intro'],
        ])->assertOk();

        $this->assertTrue($p->activities()->where('type', ProspectActivity::EMAIL_OPENED)->exists());
    }
}
