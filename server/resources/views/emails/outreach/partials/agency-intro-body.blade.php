{{--
    Fallback copy for the Agency Intro — used when no active email_templates row exists
    for `agency_intro`. The seeder loads this same wording into that row, so the two do
    not drift; edit both, or edit the row and let this stand as the recovery version.

    No {{ tokens }} in the body on purpose: the greeting and sign-off arrive through the
    slots below, which keeps this readable and keeps a blast of it merge-free.
--}}
<!--greeting-->

<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">
    I'll keep this short. divStrong is a small development shop in Richmond, VA, and we
    work almost entirely white label — behind agencies, studios and media teams who have
    won something that needs building and don't have engineers sitting idle waiting for it.
</p>

<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">
    You know the shape of it: the pitch goes well, the client wants a portal or an
    integration or an app bolted onto the campaign, and suddenly you're either scoping
    something you can't staff or handing margin to a dev shop you have to project-manage.
</p>

<p style="margin:0 0 14px; color:#e5e7eb; font-size:16px; line-height:1.6;">
    What working with us actually looks like:
</p>

<ul style="margin:0 0 24px; padding-left: 20px; color:#9ca3af; font-size:16px; line-height:1.8;">
    <li><strong style="color:#ffffff;">Invisible.</strong> We're your team. Your brand on everything, your name in the room, NDA signed before we start.</li>
    <li><strong style="color:#ffffff;">Fast.</strong> Our whole delivery process is AI-enabled, so estimates come back in hours and builds land in days where they used to take weeks.</li>
    <li><strong style="color:#ffffff;">Costed for resale.</strong> Priced so there's real margin left when you mark it up, which is the only version of this that works twice.</li>
    <li><strong style="color:#ffffff;">Whatever the stack is.</strong> Web apps, client portals, API and CRM integrations, automation, WordPress through to custom Laravel and React.</li>
</ul>

<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">
    If you've got something on the board right now, send it over and I'll come back with
    an honest scope and number — no charge, and no pitch deck. If not, worth a fifteen
    minute call so you know who to ring the next time a build lands in your lap.
</p>

{{-- The booking button is rendered by ProspectSalesMail, not written here: the rich
     editor strips a styled anchor and its href on the first save, which would send a
     dead call to action. See ProspectSalesMail::ctaHtmlFor(). --}}
<!--cta-->

<!--signature-->
