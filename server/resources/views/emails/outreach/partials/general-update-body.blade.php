{{--
    Fallback copy for the General Update — used when no active email_templates row exists
    for `general_update`.

    Deliberately thin. Unlike the two intros this email has no fixed argument: it carries
    whatever the month's news is, and the expectation is that the copy gets rewritten in
    Marketing → Email Templates before each send. What is here is a skeleton that reads as
    a complete, harmless email if somebody sends it without editing — not a placeholder
    that would go out saying "lorem ipsum" to a stranger.
--}}
<!--greeting-->

<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">
    A quick note from divStrong. We build custom software — web applications, client
    portals, integrations and automation — for companies and for the agencies who serve
    them, and we work fast: our delivery process is AI-enabled end to end, which turns
    most "next quarter" projects into this-month projects.
</p>

<p style="margin:0 0 20px; color:#9ca3af; font-size:16px; line-height:1.6;">
    If there's something you've been meaning to get built, or you just want to know what
    a realistic scope and number look like these days, reply to this email — it comes
    straight back to me.
</p>

{{-- The booking button is rendered by ProspectSalesMail, not written here: the rich
     editor strips a styled anchor and its href on the first save, which would send a
     dead call to action. See ProspectSalesMail::ctaHtmlFor(). --}}
<!--cta-->

<!--signature-->
