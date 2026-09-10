{{--
    Live preview beside the template editor.

    Rendered into an <iframe srcdoc> rather than inline. The mail shell is a complete HTML
    document with its own <html> and <head>, and it paints a dark ground — dropping that
    into the admin page would leak email styles into Filament and let Filament's styles
    repaint the email, so the preview would be a lie in both directions. An iframe gives it
    its own document, which is also exactly what a mail client does.
--}}
@php
    // $get is not exposed to a view field, and $getState() would only return this field's
    // own (empty) value. The container holds the live form state, which is what the
    // preview has to read.
    $state = $getContainer()->getRawState();
    $record = $getRecord();

    // The rich editor's RAW state is a TipTap document array, not the HTML that eventually
    // gets stored — so this is the one field that cannot be read straight off the container.
    // RichContentRenderer takes either form, which also keeps the preview working if the
    // editor is ever switched to json() storage.
    $rawBody = $state['body'] ?? '';
    $bodyHtml = is_array($rawBody)
        ? \Filament\Forms\Components\RichEditor\RichContentRenderer::make($rawBody)->toHtml()
        : (string) $rawBody;

    $vars = [
        'prospect_name' => 'Dana Ruiz',
        'company' => 'Northbound Creative',
        'notes' => null,
        'sender_name' => auth()->user()?->name ?: \App\Mail\ProspectSalesMail::FALLBACK_SENDER_NAME,
        'sender_email' => auth()->user()?->email ?: \App\Mail\ProspectSalesMail::FALLBACK_SENDER_EMAIL,
        'schedule_url' => config('prospecting.outreach.schedule_url'),
    ];

    $subject = strip_tags(\App\Models\EmailTemplate::interpolate((string) ($state['subject'] ?? ''), $vars));
    $body = \App\Models\EmailTemplate::interpolate($bodyHtml, $vars);

    // Filled through the mailable's own helper, so what is on screen is exactly what a
    // recipient gets — greeting, booking button and sign-off included. Keeping a second copy
    // of that markup here is how a preview starts lying.
    $ctaLabel = ($record?->key ?? null) === \App\Models\EmailTemplate::GENERAL_UPDATE
        ? 'Get in touch'
        : 'Book 15 minutes';

    $body = \App\Mail\ProspectSalesMail::fillSlotsFor(
        $body,
        greeting: \App\Mail\ProspectSalesMail::greetingHtmlFor($vars['prospect_name']),
        cta: \App\Mail\ProspectSalesMail::ctaHtmlFor($ctaLabel),
        signature: \App\Mail\ProspectSalesMail::signatureHtmlFor($vars['sender_name']),
    );

    $html = view('emails.outreach.shell', [
        'bodyHtml' => $body,
        'subject' => $subject,
        'brandUrl' => \App\Mail\ProspectSalesMail::BRAND_URL,
        // Shown, because the compliance block is part of what a recipient sees and the
        // person editing copy should be able to tell at a glance whether it is there.
        // A blank postal address here is a real finding, not a preview artefact.
        'postalAddress' => \App\Support\Outreach::postalAddress(),
        'unsubscribeUrl' => '#',
    ])->render();
@endphp

<div class="ds-preview">
    <div class="ds-preview__label">
        <strong>Live preview</strong>
        <span>Sample data</span>
    </div>

    <div class="ds-preview__frame">
        {{-- The subject line, shown the way an inbox shows it. --}}
        <div class="ds-preview__subject">
            <p>{{ $subject ?: '(no subject)' }}</p>
            <p>{{ $vars['sender_name'] }} &lt;{{ config('mail.from.address') }}&gt;</p>
        </div>

        {{-- An iframe rather than inline markup: the mail shell is a complete HTML document
             that paints its own dark ground, so inlining it would leak email styles into the
             panel and let the panel repaint the email. sandbox="" keeps the preview inert. --}}
        <iframe title="Email preview" sandbox="" srcdoc="{{ $html }}"></iframe>
    </div>
</div>
