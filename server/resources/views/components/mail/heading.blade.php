{{--
    Card headline: small red eyebrow, the subject itself as the H1, then any supporting
    copy passed in the slot. The eyebrow carries the message type, which frees the H1 to
    carry the thing the reader actually cares about — a project title, a name, an amount.
--}}
@props([
    'eyebrow' => null,
    'title' => null,
    'space' => 0,
])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td>
            @if($eyebrow)
            <p style="margin: 0 0 14px 0; color: #ed2537; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase;">
                {{ $eyebrow }}
            </p>
            @endif
            @if($title)
            <h1 class="sm-h1" style="margin: 0 0 {{ trim($slot) === '' ? '0' : '20' }}px 0; color: #0f1115; font-size: 30px; line-height: 1.22; font-weight: 700; letter-spacing: -0.4px;">
                {{ $title }}
            </h1>
            @endif
            {{ $slot }}
        </td>
    </tr>
</table>
