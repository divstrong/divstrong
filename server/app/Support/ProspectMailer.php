<?php

namespace App\Support;

use App\Models\ProspectActivity;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Sends a mailable to a prospect and logs an email_sent activity per recipient.
 *
 * Correlation with later open/click webhooks is done with Postmark metadata, NOT the message
 * id. The message id only works when sending through Postmark's API transport: over SMTP,
 * Symfony falls back to the local Message-ID header or the MTA queue id (a real send here
 * stored "9CAA5410881"), which never matches the GUID Postmark puts in its webhooks — so every
 * open and click was silently dropped as an unknown message.
 *
 * X-PM-Metadata-* headers survive SMTP, come back on every Open/Click/Bounce event, and point
 * straight at the prospect instead of requiring a lookup through the original send.
 */
class ProspectMailer
{
    /** Postmark caps metadata keys at 20 characters and values at 80. */
    public const META_PROSPECT_ID = 'prospect-id';

    public const META_LABEL = 'label';

    /**
     * @param  array<int, string>  $emails
     */
    public static function send($prospect, Mailable $mailable, array $emails, string $label): void
    {
        // The last gate before anything leaves. The UI hides the Send actions for an opted-out
        // prospect, but this is the one place every outreach path
        // runs through, so this is where the guarantee has to be enforceable rather than
        // remembered. Silent by design: nothing about a refusal here belongs in the recipient's
        // inbox, and the caller's job is only to not have asked.
        if (method_exists($prospect, 'isUnsubscribed') && $prospect->isUnsubscribed()) {
            \Illuminate\Support\Facades\Log::warning('Blocked outreach to an unsubscribed prospect', [
                'prospect_id' => $prospect->id ?? null,
                'label' => $label,
            ]);

            return;
        }

        static::stampMetadata($mailable, $prospect, $label);

        foreach ($emails as $email) {
            $sent = Mail::to($email)->send($mailable);

            // Kept as a secondary correlation key: it is the real Postmark GUID when sending
            // through the API transport, and a harmless queue id over SMTP.
            $messageId = null;
            try {
                $messageId = $sent?->getSymfonySentMessage()?->getMessageId();
            } catch (\Throwable) {
                // Some transports don't expose a message id — correlation is best-effort.
            }

            ProspectActivity::record([
                'prospect_id' => $prospect->id,
                'type' => ProspectActivity::EMAIL_SENT,
                'user_id' => auth()->id(),
                'description' => $label . ' sent to ' . $email,
                'meta' => [
                    'email' => $email,
                    'label' => $label,
                ],
                'external_id' => $messageId,
            ]);
        }
    }

    /**
     * Registered once for the whole recipient loop. The callback removes before adding so that
     * re-sending the same mailable instance cannot stack duplicate headers.
     */
    protected static function stampMetadata(Mailable $mailable, $prospect, string $label): void
    {
        $mailable->withSymfonyMessage(function (SymfonyEmail $message) use ($prospect, $label) {
            $headers = $message->getHeaders();

            foreach ([
                'X-PM-Metadata-' . self::META_PROSPECT_ID => (string) $prospect->id,
                'X-PM-Metadata-' . self::META_LABEL => mb_substr($label, 0, 80),
            ] as $name => $value) {
                $headers->remove($name);
                $headers->addTextHeader($name, $value);
            }
        });
    }
}
