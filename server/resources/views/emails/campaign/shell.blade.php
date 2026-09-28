{{--
    The shell every drip-campaign email renders into.

    $bodyHtml is admin-authored copy from email_templates (or the Blade fallback), already
    greeted and signed. Everything structural — the preview card, the button, the footer —
    lives here rather than in the editable copy, because the rich editor strips the markup
    those need and a campaign email with a dead CTA is worse than no campaign at all.
--}}
<x-mail.layout
    :title="'A website concept for ' . ($prospect->company ?: 'your shop')"
    :preheader="$preheader"
    :footer="$postalAddress ?? null"
>
    {{-- The admin-authored pitch. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="color: #4b5563; font-size: 16px; line-height: 1.65;">
                {!! $bodyHtml !!}
            </td>
        </tr>
    </table>

    {{-- The concept itself: a screenshot where one exists, otherwise a card that still
         reads as something built rather than a bare link. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="height: 32px; line-height: 32px; font-size: 0;">&nbsp;</td></tr>
        <tr>
            <td>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background-color: #f8f9fa; border: 1px solid #ebedf0; border-radius: 12px;">
                    @if($previewImage)
                        <tr>
                            <td style="padding: 8px 8px 0 8px;">
                                <a href="{{ $previewUrl }}" style="text-decoration: none;">
                                    <img src="{{ $previewImage }}"
                                         alt="{{ $prospect->company ? 'Website concept for ' . $prospect->company : 'Your website concept' }}"
                                         width="536"
                                         style="display: block; width: 100%; max-width: 536px; height: auto; border-radius: 8px; border: 1px solid #e4e6ea;">
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td align="center" style="padding: 24px;">
                            <p style="margin: 0 0 4px 0; color: #ed2537; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase;">
                                Built for you
                            </p>
                            <p style="margin: 0 0 20px 0; color: #0f1115; font-size: 18px; font-weight: 700; line-height: 1.35;">
                                {{ $prospect->company ?: 'Your new site' }}
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="sm-btn-wrap" style="margin: 0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#ed2537" style="border-radius: 10px;">
                                        <a href="{{ $previewUrl }}" class="sm-btn"
                                           style="display: inline-block; padding: 17px 52px; color: #ffffff; font-size: 16px; font-weight: 700; line-height: 1; letter-spacing: 0.2px; text-decoration: none; border-radius: 10px;">
                                            {{ $ctaLabel }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 14px 0 0 0; color: #9aa0aa; font-size: 12px; line-height: 1.6;">
                                No sign-up, nothing to install — it is already live.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Sign-off, after the concept card: signing off and then showing the thing you are
         pitching reads backwards. Skipped when the copy placed its own. --}}
    @if($signatureHtml)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="color: #4b5563; font-size: 16px; line-height: 1.65;">
                    {!! $signatureHtml !!}
                </td>
            </tr>
        </table>
    @endif

    {{-- Opt-out. Cold commercial mail, so this is not optional; the layout's own footer
         carries the postal address. --}}
    @if(! empty($unsubscribeUrl))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td style="height: 32px; line-height: 32px; font-size: 0;">&nbsp;</td></tr>
            <tr>
                <td align="center" style="border-top: 1px solid #ebedf0; padding-top: 20px;">
                    <p style="margin: 0; color: #9aa0aa; font-size: 12px; line-height: 1.7;">
                        Not the right time?
                        <a href="{{ $unsubscribeUrl }}" style="color: #8b919c; text-decoration: underline;">Unsubscribe</a>
                        and I will not write again.
                    </p>
                </td>
            </tr>
        </table>
    @endif
</x-mail.layout>
