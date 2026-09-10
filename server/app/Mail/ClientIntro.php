<?php

namespace App\Mail;

use App\Models\EmailTemplate;

/**
 * "Client Intro" — sent to companies that would buy delivery directly.
 *
 * Different argument to the Agency Intro: not "we fill your gap" but "we ship faster
 * than what you are used to". The claim is specific and checkable — days instead of
 * weeks, hours instead of days — because a vague speed claim is what every agency
 * email says and none of them mean.
 */
class ClientIntro extends ProspectSalesMail
{
    protected function templateKey(): string
    {
        return EmailTemplate::CLIENT_INTRO;
    }

    protected function defaultSubject(): string
    {
        return 'Custom software in days, not weeks';
    }

    protected function bodyView(): string
    {
        return 'emails.outreach.partials.client-intro-body';
    }
}
