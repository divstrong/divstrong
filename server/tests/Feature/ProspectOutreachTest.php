<?php

namespace Tests\Feature;

use App\Mail\AgencyIntro;
use App\Mail\ClientIntro;
use App\Mail\GeneralUpdate;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Support\Outreach;
use App\Support\ProspectMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sending cold email: what goes out, what must never go out, and how somebody stops it.
 *
 * The opt-out cases are the ones worth having. Mailing a person who has asked us to stop is a
 * broken legal promise with a timestamp proving we knew, and the guarantee has to be
 * enforceable at the send path rather than remembered at each of the places that call it.
 */
class ProspectOutreachTest extends TestCase
{
    use RefreshDatabase;

    private function prospect(array $attributes = []): Prospect
    {
        return Prospect::create(array_merge([
            'name' => 'Dana Ruiz',
            'company' => 'Northbound Creative',
            'email' => 'dana@northbound.test',
            'segment' => Prospect::SEGMENT_AGENCY,
        ], $attributes));
    }

    public static function mailableProvider(): array
    {
        return [
            'agency intro' => [AgencyIntro::class, 'Agency Intro'],
            'client intro' => [ClientIntro::class, 'Client Intro'],
            'general update' => [GeneralUpdate::class, 'General Update'],
        ];
    }

    #[DataProvider('mailableProvider')]
    public function test_every_outreach_email_renders_complete(string $mailable, string $label): void
    {
        $p = $this->prospect();

        $html = (new $mailable($p, ['notes' => 'Following up on our call.']))->render();

        $this->assertStringContainsString('Hi Dana Ruiz,', $html, 'greeting slot must be filled');
        $this->assertStringContainsString('Following up on our call.', $html, 'personal note must appear');
        $this->assertStringContainsString('Best,', $html, 'signature slot must be filled');
        $this->assertStringNotContainsString('<!--greeting-->', $html);
        $this->assertStringNotContainsString('<!--cta-->', $html);
        $this->assertStringNotContainsString('<!--signature-->', $html);
        // CAN-SPAM: a commercial message with no working opt-out is a per-message violation.
        $this->assertStringContainsString('/unsubscribe/', $html);
        // A cold email with a dead call to action is a wasted send.
        $this->assertStringContainsString(
            (string) config('prospecting.outreach.schedule_url'),
            $html,
            'the booking button must carry a real href',
        );
    }

    /**
     * The regression that put the CTA in a slot in the first place.
     *
     * The rich editor does not model a table-wrapped anchor with inline styles, so a template
     * that has been opened and saved once comes back with the button flattened to plain text
     * and its href gone. Rendering it at send time means an edited template still goes out
     * with a working button — and nothing on screen would have told anyone otherwise.
     *
     */
    #[DataProvider('mailableProvider')]
    public function test_the_booking_button_survives_a_template_edit(string $mailable, string $label): void
    {
        $this->artisan('email:seed-templates')->assertSuccessful();

        $template = EmailTemplate::query()->firstOrFail();

        // Exactly what the editor stores after somebody opens the template and saves it:
        // comments stripped, raw HTML normalised away.
        $mangled = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($template->body)
            ->getEditor()
            ->getDocument();

        $template->update([
            'body' => \Filament\Forms\Components\RichEditor\RichContentRenderer::make($mangled)->toHtml(),
        ]);

        $this->assertStringNotContainsString(
            '<!--cta-->',
            $template->fresh()->body,
            'precondition: the editor really does strip the slot',
        );

        $html = (new $mailable($this->prospect()))->render();

        $this->assertStringContainsString(
            (string) config('prospecting.outreach.schedule_url'),
            $html,
            'the button must still be there after an edit',
        );
        $this->assertStringContainsString('background-color:#ed2537', $html);
        $this->assertStringContainsString('Hi Dana Ruiz,', $html, 'and so must the greeting');
        $this->assertStringContainsString('Best,', $html, 'and the sign-off');
    }

    /**
     * Deactivating a template is the recovery path when an edit goes wrong, so the built-in
     * copy has to be a complete, sendable email rather than a stub.
     */
    #[DataProvider('mailableProvider')]
    public function test_the_blade_fallback_is_sendable_with_no_template_row(string $mailable, string $label): void
    {
        $this->assertSame(0, EmailTemplate::count(), 'this test deliberately runs with no rows');

        $html = (new $mailable($this->prospect()))->render();

        $this->assertStringContainsString('Hi Dana Ruiz,', $html);
        $this->assertStringContainsString('Best,', $html);
        $this->assertStringContainsString(
            (string) config('prospecting.outreach.schedule_url'),
            $html,
            'the fallback body must resolve its own variables',
        );
    }

    public function test_a_seeded_template_overrides_the_fallback(): void
    {
        $this->artisan('email:seed-templates')->assertSuccessful();

        $template = EmailTemplate::where('key', EmailTemplate::AGENCY_INTRO)->firstOrFail();
        $template->update(['body' => '<p>ENTIRELY REWRITTEN</p>', 'subject' => 'Rewritten subject']);

        $mail = new AgencyIntro($this->prospect());

        $this->assertStringContainsString('ENTIRELY REWRITTEN', $mail->render());
        $this->assertSame('Rewritten subject', $mail->envelope()->subject);

        // Deactivating must fall back rather than send an empty message.
        $template->update(['is_active' => false]);
        $this->assertStringNotContainsString('ENTIRELY REWRITTEN', (new AgencyIntro($this->prospect()))->render());
    }

    public function test_the_sender_is_carried_but_the_from_domain_is_not(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'Jim Strong', 'email' => 'jim@divstrong.com']);

        $envelope = (new AgencyIntro($this->prospect(), [], $user))->envelope();

        // Postmark will not sign mail claiming to come from a domain it does not host, so the
        // From address stays put and Reply-To is what routes the answer back to the rep.
        $this->assertSame(config('mail.from.address'), $envelope->from->address);
        $this->assertSame('Jim Strong', $envelope->from->name);
        $this->assertSame('jim@divstrong.com', $envelope->replyTo[0]->address);
    }

    public function test_sending_records_one_activity_per_recipient(): void
    {
        Mail::fake();
        $p = $this->prospect();

        ProspectMailer::send($p, new AgencyIntro($p), ['a@x.test', 'b@x.test'], 'Agency Intro');

        Mail::assertSent(AgencyIntro::class, 2);
        $this->assertSame(2, $p->activities()->where('type', ProspectActivity::EMAIL_SENT)->count());
        $this->assertTrue($p->fresh()->hasSentEmail('Agency Intro'));
    }

    /**
     * The one guarantee this whole feature stands on.
     */
    public function test_an_opted_out_prospect_is_never_mailed(): void
    {
        Mail::fake();
        $p = $this->prospect();
        $p->unsubscribe(Prospect::UNSUB_LINK);

        ProspectMailer::send($p->fresh(), new AgencyIntro($p), [$p->email], 'Agency Intro');

        Mail::assertNothingSent();
        $this->assertFalse($p->fresh()->hasSentEmail('Agency Intro'), 'and nothing is logged as sent');
    }

    public function test_opting_out_is_idempotent_and_keeps_the_original_date(): void
    {
        $p = $this->prospect();

        $p->unsubscribe(Prospect::UNSUB_LINK);
        $first = $p->fresh()->unsubscribed_at;

        $this->travel(1)->days();
        $p->fresh()->unsubscribe(Prospect::UNSUB_COMPLAINT);

        // The date we were first told is the date that matters if anyone ever asks.
        $this->assertTrue($p->fresh()->unsubscribed_at->eq($first));
        $this->assertSame(Prospect::UNSUB_LINK, $p->fresh()->unsubscribe_source);
    }

    public function test_resubscribing_re_enables_sending(): void
    {
        Mail::fake();
        $p = $this->prospect();

        $p->unsubscribe();
        $this->assertFalse(Prospect::query()->mailable()->whereKey($p->id)->exists());

        $p->fresh()->resubscribe();
        $this->assertTrue(Prospect::query()->mailable()->whereKey($p->id)->exists());

        ProspectMailer::send($p->fresh(), new AgencyIntro($p), [$p->email], 'Agency Intro');
        Mail::assertSent(AgencyIntro::class);
    }

    public function test_the_opt_out_link_is_signed_and_not_forgeable(): void
    {
        $mine = $this->prospect();
        $theirs = $this->prospect(['email' => 'victim@other.test']);

        $url = Outreach::unsubscribeUrl($mine);
        $this->assertNotNull($url);

        // Editing the id to opt a competitor out must fail on the signature.
        $forged = str_replace('/'.$mine->id.'?', '/'.$theirs->id.'?', $url);

        $this->get($forged)->assertForbidden();
        $this->post($forged)->assertForbidden();
        $this->assertFalse($theirs->fresh()->isUnsubscribed());
    }

    /**
     * A GET must not change anything: mail clients prefetch links and security appliances
     * scan them, and either would silently opt somebody out who never clicked.
     */
    public function test_get_confirms_and_post_performs(): void
    {
        $p = $this->prospect();
        $url = Outreach::unsubscribeUrl($p);

        $this->get($url)->assertOk()->assertSee($p->email);
        $this->assertFalse($p->fresh()->isUnsubscribed());

        $this->post($url)->assertOk();
        $this->assertTrue($p->fresh()->isUnsubscribed());
        $this->assertTrue($p->activities()->where('type', ProspectActivity::UNSUBSCRIBED)->exists());
    }

    /**
     * RFC 8058: a provider reads any non-2xx as a broken unsubscribe mechanism, and being
     * flagged for that is exactly the reputational damage this exists to avoid.
     */
    public function test_a_repeat_one_click_post_still_answers_200(): void
    {
        $p = $this->prospect();
        $url = Outreach::unsubscribeUrl($p);

        $this->post($url)->assertOk();
        $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk();
    }

    /**
     * Since February 2024 Gmail and Yahoo require these of bulk senders, and the clients that
     * honour them turn a would-be spam complaint into a quiet opt-out.
     *
     * Asserted against a really-sent message rather than a faked one: the headers are stamped
     * through withSymfonyMessage, which only runs when a transport actually builds the mail —
     * so Mail::fake() would happily pass a mailable that stamps nothing.
     */
    public function test_outreach_mail_carries_the_list_unsubscribe_headers(): void
    {
        $p = $this->prospect();

        ProspectMailer::send($p, new AgencyIntro($p), [$p->email], 'Agency Intro');

        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        $headers = $messages[0]->getOriginalMessage()->getHeaders();

        $this->assertTrue($headers->has('List-Unsubscribe'));
        $this->assertStringContainsString('/unsubscribe/', $headers->get('List-Unsubscribe')->getBodyAsString());
        $this->assertSame(
            'List-Unsubscribe=One-Click',
            $headers->get('List-Unsubscribe-Post')->getBodyAsString(),
            'RFC 8058 one-click is what makes the mail client offer an unsubscribe button.',
        );

        // The same send also stamps the metadata the Postmark webhook correlates events by.
        // Over SMTP the message id is an MTA queue id that never matches Postmark's GUID, so
        // these headers are the only reliable link back to the prospect.
        $this->assertSame(
            (string) $p->id,
            $headers->get('X-PM-Metadata-'.ProspectMailer::META_PROSPECT_ID)->getBodyAsString(),
        );
        $this->assertSame(
            'Agency Intro',
            $headers->get('X-PM-Metadata-'.ProspectMailer::META_LABEL)->getBodyAsString(),
        );
    }
}
