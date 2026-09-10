<?php

namespace App\Support;

use App\Models\Prospect;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * The compliance furniture that has to ride along with every piece of cold outreach.
 *
 * Two obligations, one place, so a new outreach email cannot be written without them:
 *
 *   1. A postal address and a working opt-out link in the visible footer. CAN-SPAM
 *      requires both on commercial email.
 *   2. List-Unsubscribe headers. Not legally required, but since February 2024 Gmail
 *      and Yahoo require them from bulk senders, and the mail clients that honour
 *      them turn a would-be spam complaint into a quiet opt-out.
 *
 * Deliberately NOT applied to transactional mail. An unsubscribe link on a proposal
 * or a payment receipt invites people to opt out of their own paperwork, and those
 * messages carry no marketing, so the Act does not ask for one.
 */
class Outreach
{
    /**
     * The opt-out URL for a prospect.
     *
     * Signed rather than tokenised: no column to add, no token to leak, and the
     * signature makes the id untamperable, so nobody can unsubscribe a competitor by
     * editing the number in the URL. Never expires — cold email gets read months
     * late, and an opt-out link that has timed out is an opt-out mechanism that does
     * not work.
     */
    public static function unsubscribeUrl(Prospect $prospect): ?string
    {
        // The preview routes build an unsaved prospect purely to render the template.
        // Nothing is being sent, so there is nothing to opt out of, and signing a
        // route for a null id would throw.
        if (! $prospect->exists || $prospect->id === null) {
            return null;
        }

        return URL::signedRoute('outreach.unsubscribe', ['prospect' => $prospect->id]);
    }

    /** The sender's physical address, or null when nobody has configured one yet. */
    public static function postalAddress(): ?string
    {
        $address = config('prospecting.outreach.postal_address');

        return filled($address) ? trim($address) : null;
    }

    /**
     * Footer variables for the outreach mail shell.
     *
     * @return array<string, mixed>
     */
    public static function footerVars(Prospect $prospect): array
    {
        return [
            'unsubscribeUrl' => static::unsubscribeUrl($prospect),
            'postalAddress' => static::postalAddress(),
        ];
    }

    /**
     * Attach the List-Unsubscribe headers.
     *
     * List-Unsubscribe-Post turns the mail client's own "unsubscribe" control into a
     * single POST to our URL (RFC 8058), with no page to visit and nothing to confirm
     * — which is both what Gmail and Yahoo now require of bulk senders, and the reason
     * a recipient reaches for that button instead of "report spam".
     *
     * Registered through withSymfonyMessage and removed before adding, so re-sending
     * one mailable instance to several addresses cannot stack duplicates.
     */
    public static function stampUnsubscribeHeaders(Mailable $mailable, Prospect $prospect): void
    {
        $url = static::unsubscribeUrl($prospect);
        $mailto = config('prospecting.outreach.unsubscribe_mailto');

        if ($url === null) {
            return;
        }

        $targets = ['<'.$url.'>'];

        if (filled($mailto)) {
            $targets[] = '<mailto:'.$mailto.'?subject=unsubscribe>';
        }

        $mailable->withSymfonyMessage(function (SymfonyEmail $message) use ($targets) {
            $headers = $message->getHeaders();

            foreach ([
                'List-Unsubscribe' => implode(', ', $targets),
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ] as $name => $value) {
                $headers->remove($name);
                $headers->addTextHeader($name, $value);
            }
        });
    }
}
