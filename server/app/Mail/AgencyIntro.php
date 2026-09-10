<?php

namespace App\Mail;

use App\Models\EmailTemplate;

/**
 * "Agency Intro" — the white-label pitch, sent to creative and media shops.
 *
 * The argument is a gap in their own bench, not a service menu: they win work that
 * needs building and have nobody to build it, so it gets turned away or handed to a
 * subcontractor they have to manage. We are the subcontractor that does not need
 * managing, and we never appear in front of their client.
 *
 * Everything structural comes from ProspectSalesMail.
 */
class AgencyIntro extends ProspectSalesMail
{
    protected function templateKey(): string
    {
        return EmailTemplate::AGENCY_INTRO;
    }

    protected function defaultSubject(): string
    {
        return 'A dev bench for your agency, without the headcount';
    }

    protected function bodyView(): string
    {
        return 'emails.outreach.partials.agency-intro-body';
    }
}
