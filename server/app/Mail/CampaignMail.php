<?php

namespace App\Mail;

use App\Models\CampaignStep;
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
 * One step of a drip campaign.
 *
 * Structurally a sibling of ProspectSalesMail — editable database copy, a Blade fallback,
 * signed by a real person, CAN-SPAM footer — with two deliberate differences:
 *
 *   1. It renders through the light mail components the rest of the app uses, not the dark
 *      outreach shell. These go to strangers whose first impression of our design taste is
 *      this email.
 *   2. The call to action points at the prospect's own preview page, not a generic booking
 *      link. The whole pitch is "we already built you one"; a CTA that does not lead to it
 *      wastes the only thing this email has to say.
 */
class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Where personalisation lands if the stored copy marks the spots. Missing is normal. */
    public const GREETING_SLOT = '<!--greeting-->';

    public const CTA_SLOT = '<!--cta-->';

    public const SIGNATURE_SLOT = '<!--signature-->';

    /**
     * Where the concept card sits. Written in the copy as a {{ concept }} paragraph rather
     * than a comment, because the rich editor drops HTML comments on save.
     */
    public const CONCEPT_SLOT = '<!--concept-->';

    /** Copy written after the {{ concept }} marker, rendered below the card. */
    protected string $afterConceptHtml = '';

    public const FALLBACK_SENDER_NAME = 'Jim Doyle';

    public const FALLBACK_SENDER_EMAIL = 'jim@divstrong.com';

    public string $senderName;

    public string $senderEmail;

    protected ?EmailTemplate $template;

    /** Set when the stored copy places its own sign-off, so the shell does not add a second. */
    protected bool $signatureInBody = false;

    public function __construct(
        public Prospect $prospect,
        public CampaignStep $step,
        ?User $sender = null,
    ) {
        // Captured at construction: a queued or scheduled send is built by a worker with
        // no authenticated user, and cold mail signed "The divStrong Team" reads as bulk.
        $sender ??= auth()->user();
        $this->senderName = $sender?->name ?: static::FALLBACK_SENDER_NAME;
        $this->senderEmail = $sender?->email ?: static::FALLBACK_SENDER_EMAIL;

        $this->template = EmailTemplate::forKey($step->template_key);
    }

    /**
     * @return array<string, mixed>
     */
    public function templateVars(): array
    {
        return [
            'first_name' => $this->prospect->firstName(),
            'prospect_name' => $this->prospect->name ?: ($this->prospect->company ?: 'there'),
            'company' => $this->prospect->company ?? '',
            'website' => $this->prospect->website ?? '',
            'preview_url' => $this->previewUrl(),
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
        ];
    }

    /**
     * The tracked landing page, falling back to the design itself.
     *
     * Only the landing page can record the answers and offer the calendar, so the raw
     * preview URL is a last resort for a prospect whose token could not be minted.
     */
    public function previewUrl(): string
    {
        return $this->prospect->previewLandingUrl()
            ?? $this->prospect->preview_url
            ?? url('/book');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            // From stays on the verified sending domain — Postmark will not sign mail
            // claiming to come from elsewhere — while carrying the rep's name. Reply-To
            // is what routes the answer back to a human.
            from: new Address(config('mail.from.address'), $this->senderName),
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: $this->resolvedSubject(),
        );
    }

    public function resolvedSubject(): string
    {
        $subject = $this->template?->renderSubject($this->templateVars());

        return filled($subject)
            ? $subject
            : 'A website concept for ' . ($this->prospect->company ?: 'your business');
    }

    public function content(): Content
    {
        // Both paths run through interpolate(): the fallback partials carry the same
        // literal {{ tokens }} as the stored rows, so copy cannot mean one thing in the
        // editor and another when the row is deactivated.
        //
        // The concept marker is swapped for its slot on the raw copy, before any
        // interpolation, or it would be blanked as an unknown placeholder.
        $body = EmailTemplate::interpolate(
            static::markConceptSlot(
                $this->template
                    ? $this->template->body
                    : view($this->fallbackView(), $this->templateVars())->render()
            ),
            $this->templateVars(),
        );

        return new Content(
            view: 'emails.campaign.shell',
            with: [
                // fillSlots() runs first: it decides whether the copy places its own
                // sign-off, which the shell needs to know before it renders one.
                'bodyHtml' => $bodyHtml = $this->fillSlots($body),
                'afterConceptHtml' => $this->afterConceptHtml,
                'signatureHtml' => $this->signatureInBody ? null : static::signatureHtml($this->senderName),
                'prospect' => $this->prospect,
                'previewUrl' => $this->previewUrl(),
                'previewImage' => $this->previewImageUrl(),
                'ctaLabel' => $this->ctaLabel(),
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'preheader' => $this->preheader(),
                // Cold outreach: the footer carries the postal address and opt-out link
                // CAN-SPAM requires.
                ...Outreach::footerVars($this->prospect),
            ],
        );
    }

    /**
     * The Blade partial standing in for a template row that is missing or deactivated.
     *
     * Named after the step's key — promo_preview_intro becomes steps/preview-intro — so a
     * new step written the same way needs no wiring here.
     */
    protected function fallbackView(): string
    {
        $slug = str_replace('_', '-', preg_replace('/^promo_/', '', $this->step->template_key));
        $view = 'emails.campaign.steps.' . $slug;

        return view()->exists($view) ? $view : 'emails.campaign.fallback-body';
    }

    protected function previewImageUrl(): ?string
    {
        $image = $this->prospect->preview_image;

        if (blank($image)) {
            return null;
        }

        return str_starts_with($image, 'http')
            ? $image
            : asset('storage/' . ltrim($image, '/'));
    }

    protected function preheader(): string
    {
        return 'We built a website concept for ' . ($this->prospect->company ?: 'your business') . ' — take a look.';
    }

    protected function ctaLabel(): string
    {
        return 'See the concept';
    }

    public function build(): static
    {
        Outreach::stampUnsubscribeHeaders($this, $this->prospect);

        return $this;
    }

    /**
     * Drop the greeting into the stored copy, and the sign-off if it asks for one.
     *
     * The CTA is NOT injected into the body: the shell renders it as a real button beside
     * the preview image, because a table-wrapped anchor does not survive a round trip
     * through the rich editor — it comes back as plain text linking nowhere.
     */
    protected function fillSlots(string $body): string
    {
        $greeting = static::greetingHtml($this->prospect->firstName());

        $body = str_contains($body, static::GREETING_SLOT)
            ? str_replace(static::GREETING_SLOT, $greeting, $body)
            : $greeting . $body;

        // Stripped rather than filled: the shell renders the button beside the concept
        // card, which is where it belongs. Copy carried over from the outreach templates
        // would otherwise show a stray comment.
        $body = str_replace(static::CTA_SLOT, '', $body);

        // The rich editor drops inline styles on save, and a bare link in grey body copy
        // reads as plain text in most clients. Give any unstyled link the brand treatment.
        $body = preg_replace(
            '/<a(?![^>]*\bstyle=)(?=[\s>])/i',
            '<a style="color:#ed2537; text-decoration:underline;"',
            $body,
        ) ?? $body;

        // Everything after the concept marker moves below the card.
        if (str_contains($body, static::CONCEPT_SLOT)) {
            [$body, $after] = explode(static::CONCEPT_SLOT, $body, 2);
            $this->afterConceptHtml = trim(str_replace(static::CONCEPT_SLOT, '', $after));
        }

        // Default position for the sign-off is AFTER the concept card — signing off and
        // then showing the thing you are pitching reads backwards. Copy that marks its
        // own slot overrides that.
        if (str_contains($body, static::SIGNATURE_SLOT)) {
            $this->signatureInBody = true;

            return str_replace(static::SIGNATURE_SLOT, static::signatureHtml($this->senderName), $body);
        }

        return $body;
    }

    /**
     * Turn a {{ concept }} marker — bare, or the paragraph the editor wraps it in — into the
     * slot comment. Runs before interpolate() so the token is not blanked as unknown.
     */
    protected static function markConceptSlot(string $body): string
    {
        return preg_replace(
            '/(?:<p[^>]*>\s*)?\{\{\s*concept\s*\}\}(?:\s*<\/p>)?/i',
            static::CONCEPT_SLOT,
            $body,
        ) ?? $body;
    }

    public static function greetingHtml(string $firstName): string
    {
        return '<p style="margin:0 0 16px; color:#4b5563; font-size:16px; line-height:1.65;">Hi '
            . e($firstName) . ',</p>';
    }

    /**
     * The sign-off. A fixed signer from config rather than whoever pressed the button: the
     * campaign is pitched in the founder's voice, and a user account named "Jim" signing
     * cold mail reads as half-finished.
     */
    public static function signatureHtml(string $senderName): string
    {
        $name = config('prospecting.outreach.signature_name') ?: $senderName;
        $title = config('prospecting.outreach.signature_title');

        return '<p style="margin:28px 0 0; color:#4b5563; font-size:16px; line-height:1.65;">Best,<br />'
            . '<strong style="color:#0f1115;">' . e($name) . ($title ? ',' : '') . '</strong>'
            . ($title ? '<br />' . e($title) : '')
            . '</p>';
    }

    public function attachments(): array
    {
        return [];
    }
}
