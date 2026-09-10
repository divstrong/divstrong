<?php

namespace App\Mail;

use App\Models\EmailTemplate;

/**
 * "General Update" — the open one.
 *
 * News, a launch, an event, an offer, whatever the month's topic is. It ships with a
 * deliberately thin fallback body, because unlike the two intros this email has no
 * fixed argument: the copy is expected to be rewritten in Marketing → Email Templates
 * before each send, and the personal note on the send modal covers the one-off case.
 */
class GeneralUpdate extends ProspectSalesMail
{
    protected function templateKey(): string
    {
        return EmailTemplate::GENERAL_UPDATE;
    }

    protected function defaultSubject(): string
    {
        return 'What we have been building at divStrong';
    }

    protected function bodyView(): string
    {
        return 'emails.outreach.partials.general-update-body';
    }

    /**
     * Softer than the intros' "Book 15 minutes": this email is not always asking for a
     * meeting, and a booking button under a product announcement reads as a bait and switch.
     */
    protected function ctaLabel(): string
    {
        return 'Get in touch';
    }
}
