{{--
    Outcome headline for the messages that report something that already happened —
    viewed, approved, declined, paid. The badge is the only place another colour is let
    in, because green-means-paid reads faster than any wording could.
--}}
@props(['tone' => 'info', 'symbol' => '', 'title' => null, 'space' => 0])
@php
    $tones = [
        'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'fg' => '#059669'],
        'danger'  => ['bg' => '#fef2f2', 'border' => '#fecaca', 'fg' => '#dc2626'],
        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'fg' => '#b45309'],
        'info'    => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'fg' => '#2563eb'],
    ];
    $t = $tones[$tone] ?? $tones['info'];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                <tr>
                    <td width="56" height="56" align="center" valign="middle" bgcolor="{{ $t['bg'] }}" style="width: 56px; height: 56px; border: 2px solid {{ $t['border'] }}; border-radius: 28px; color: {{ $t['fg'] }}; font-size: 26px; line-height: 56px; text-align: center;">
                        {!! $symbol !!}
                    </td>
                </tr>
            </table>
            @if($title)
            <h1 class="sm-h1" style="margin: 20px 0 0 0; color: #0f1115; font-size: 28px; line-height: 1.25; font-weight: 700; letter-spacing: -0.4px; text-align: center;">
                {{ $title }}
            </h1>
            @endif
            @if(trim($slot) !== '')
            <div style="margin-top: 10px; color: #4b5563; font-size: 16px; line-height: 1.65; text-align: center;">{{ $slot }}</div>
            @endif
        </td>
    </tr>
</table>
