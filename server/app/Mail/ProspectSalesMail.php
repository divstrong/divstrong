<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\User;
use App\Support\Outreach;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Shared shape for divStrong's cold outreach (Agency Intro, Client Intro, General Update).
 *
 * Every one of these is: an editable database template with a Blade fallback, rendered
 * through the branded shell, signed by whoever is logged in, and replying to them.
 *
 * The body copy carries no {{ tokens }}. Personalisation goes into two HTML-comment
 * slots that get filled at send time, which keeps the stored template readable in the
 * rich editor and means a body edited down to nothing still arrives greeted and signed.
 *
 * Subclasses supply the template key, the fallback subject and the Blade body.
 */
abstract class ProspectSalesMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Where the personalisation and the booking button land in the body.
     *
     * HTML comments rather than {{ tokens }} so nobody editing the copy sees a raw token in
     * the middle of a sentence. They do NOT survive the rich editor — TipTap drops comments on
     * the first save, along with any raw HTML it does not model — so fillSlots() treats a
     * missing slot as "put it in the obvious place" rather than as an error. That fallback is
     * the normal case for any template that has ever been edited, not an edge case.
     */
    public const GREETING_SLOT = '<!--greeting-->';

    public const CTA_SLOT = '<!--cta-->';

    public const SIGNATURE_SLOT = '<!--signature-->';

    /** Used when there is no authenticated sender (preview routes, console, queue replay). */
    public const FALLBACK_SENDER_NAME = 'The divStrong Team';

    public const FALLBACK_SENDER_EMAIL = 'jim@divstrong.com';

    public const BRAND_URL = 'https://divstrong.com';

    public string $prospectName;

    public ?string $notes;

    public string $senderName;

    public string $senderEmail;

    protected ?EmailTemplate $template;

    public function __construct(
        public Prospect $prospect,
        public array $data = [],
        ?User $sender = null,
    ) {
        // Falls back through company to "there", because discovery can land a shared
        // inbox with no name attached and "Hi ," is worse than "Hi there,".
        $this->prospectName = $prospect->name ?: ($prospect->company ?: 'there');
        $this->notes = $data['notes'] ?? null;

        // Resolved here, not in envelope()/content(): a queued mailable is rebuilt by a
        // worker with no authenticated user, so the sender has to be captured at send time.
        $sender ??= auth()->user();
        $this->senderName = $sender?->name ?: static::FALLBACK_SENDER_NAME;
        $this->senderEmail = $sender?->email ?: static::FALLBACK_SENDER_EMAIL;

        $this->template = EmailTemplate::forKey($this->templateKey());
    }

    /** The EmailTemplate key this mail renders when an active row exists. */
    abstract protected function templateKey(): string;

    /** Subject used when no template row is active. Keep in step with the seeder. */
    abstract protected function defaultSubject(): string;

    /** Blade partial rendered when no template row is active. */
    abstract protected function bodyView(): string;

    /**
     * @return array<string, mixed>
     */
    public function templateVars(): array
    {
        return [
            'prospect_name' => $this->prospectName,
            'company' => $this->prospect->company ?? '',
            'notes' => $this->notes,
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'schedule_url' => config('prospecting.outreach.schedule_url'),
        ];
    }

    public function envelope(): Envelope
    {
        $subject = $this->template
            ? $this->template->renderSubject($this->templateVars())
            : $this->defaultSubject();

        return new Envelope(
            // The From address stays on the verified sending domain — Postmark will not
            // sign mail claiming to come from somewhere it does not host — but it carries
            // the rep's name. Reply-To is what actually routes the answer back to them.
            from: new Address(config('mail.from.address'), $this->senderName),
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        // Both paths render through the same shell, so the editable row and the no-row
        // fallback cannot drift. renderBody() still runs the token pass, so an admin who
        // *does* put {{ company }} in the stored body gets it substituted.
        $body = $this->template
            ? $this->template->renderBody($this->templateVars())
            : view($this->bodyView(), $this->templateVars())->render();

        return new Content(
            view: 'emails.outreach.shell',
            with: [
                'bodyHtml' => $this->fillSlots($body),
                'subject' => $this->template
                    ? $this->template->renderSubject($this->templateVars())
                    : $this->defaultSubject(),
                'brandUrl' => static::BRAND_URL,
                // Cold outreach, so the footer carries the postal address and opt-out
                // link CAN-SPAM requires. Client-facing mail passes neither and shows
                // neither.
                ...Outreach::footerVars($this->prospect),
            ],
        );
    }

    /**
     * List-Unsubscribe headers, added at build time so they ride on every send of this
     * mailable however it is dispatched — the row action, the edit page, a console replay.
     */
    public function build(): static
    {
        Outreach::stampUnsubscribeHeaders($this, $this->prospect);

        return $this;
    }

    /** The wording on the booking button. */
    protected function ctaLabel(): string
    {
        return 'Book 15 minutes';
    }

    protected function fillSlots(string $body): string
    {
        return static::fillSlotsFor(
            $body,
            greeting: static::greetingHtmlFor($this->prospectName, $this->notes),
            cta: static::ctaHtmlFor($this->ctaLabel()),
            signature: static::signatureHtmlFor($this->senderName),
        );
    }

    /**
     * Drop the greeting, the booking button and the sign-off into the body.
     *
     * Static and parameterised because three callers need identical output: a real send, the
     * live preview beside the editor, and the "Send Preview" test. When those drifted, the
     * preview showed a message nobody would ever receive — which is the one thing a preview
     * must not do.
     *
     * A missing slot is normal rather than exceptional (see the slot constants), so each falls
     * back to its natural position: greeting on top, button and sign-off at the end, in that
     * order. Never silently dropped — an unsigned cold email with no call to action is worse
     * than a slightly misplaced one.
     */
    public static function fillSlotsFor(string $body, string $greeting, string $cta, string $signature): string
    {
        $body = str_contains($body, static::GREETING_SLOT)
            ? str_replace(static::GREETING_SLOT, $greeting, $body)
            : $greeting.$body;

        $body = str_contains($body, static::CTA_SLOT)
            ? str_replace(static::CTA_SLOT, $cta, $body)
            : $body.$cta;

        return str_contains($body, static::SIGNATURE_SLOT)
            ? str_replace(static::SIGNATURE_SLOT, $signature, $body)
            : $body.$signature;
    }

    /**
     * Personalisation added at send time. Values are escaped — the body itself is trusted
     * admin-authored HTML, these are not.
     */
    public static function greetingHtmlFor(string $name, ?string $notes = null): string
    {
        $html = '<p style="margin:0 0 20px; color:#e5e7eb; font-size:16px; line-height:1.6;">Hi '
            .e($name).',</p>';

        if (filled($notes)) {
            $html .= '<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">'
                .e($notes).'</p>';
        }

        return $html;
    }

    /**
     * The booking button.
     *
     * Rendered here rather than stored in the template body, and for a harder reason than
     * taste: the rich editor does not model a table-wrapped anchor with inline styles, so a
     * round trip through it strips the styling AND the href. A template that had been opened
     * and saved once would go out with the words "Book 15 minutes" in plain text linking
     * nowhere — a dead call to action in a cold email, with nothing on screen to say so.
     *
     * Table-wrapped because Outlook ignores padding on an inline anchor.
     */
    public static function ctaHtmlFor(string $label): string
    {
        $url = (string) config('prospecting.outreach.schedule_url');

        return '<table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 8px;"><tr>'
            .'<td align="center">'
            .'<a href="'.e($url).'" style="display:inline-block; background-color:#ed2537; '
            .'color:#ffffff; font-weight:600; font-size:16px; text-decoration:none; '
            .'padding:14px 40px; border-radius:8px;">'.e($label).'</a>'
            .'</td></tr></table>';
    }

    /**
     * Plain sign-off. The rep's address is deliberately not printed — Reply-To already routes
     * the answer to them, and a bare mailto competes with whatever the email is asking for.
     */
    public static function signatureHtmlFor(string $senderName): string
    {
        return '<p style="margin:28px 0 0; color:#9ca3af; font-size:16px; line-height:1.6;">Best,<br />'
            .'<strong style="color:#ffffff;">'.e($senderName).'</strong><br />'
            .'<span style="color:#6b7280; font-size:14px;">divStrong</span></p>';
    }

    public function attachments(): array
    {
        return [];
    }
}
