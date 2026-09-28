{{--
    The one action a message asks for. Bulletproof: the colour sits on the td as well, so
    clients that strip the anchor's background still render a button. Full width below 620px.

    'link' repeats the destination as plain text underneath, for the clients that mangle
    anchors and for the people who forward the mail on to someone else.
--}}
@props(['url', 'space' => 36, 'link' => false])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="sm-btn-wrap" style="margin: 0 auto;">
                <tr>
                    <td align="center" bgcolor="#ed2537" style="border-radius: 10px;">
                        <a href="{{ $url }}" class="sm-btn" style="display: inline-block; padding: 17px 52px; color: #ffffff; font-size: 16px; font-weight: 700; line-height: 1; letter-spacing: 0.2px; text-decoration: none; border-radius: 10px;">
                            {{ $slot }}
                        </a>
                    </td>
                </tr>
            </table>
            @if($link)
            <p style="margin: 18px 0 0 0; color: #9aa0aa; font-size: 13px; line-height: 1.6;">
                Or paste this link into your browser:<br>
                <a href="{{ $url }}" style="color: #ed2537; text-decoration: none; word-break: break-all;">{{ $url }}</a>
            </p>
            @endif
        </td>
    </tr>
</table>
