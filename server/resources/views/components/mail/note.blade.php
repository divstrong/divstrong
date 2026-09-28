{{-- A message written by a person, set apart from the generated copy around it. --}}
@props(['label' => 'A note', 'space' => 24])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-left: 3px solid #ed2537; border-radius: 0 12px 12px 0; background-color: #fdf5f6;">
                <tr>
                    <td style="padding: 20px 24px;">
                        @if($label)
                        <p style="margin: 0 0 8px 0; color: #ed2537; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;">
                            {{ $label }}
                        </p>
                        @endif
                        <p style="margin: 0; color: #2b2f36; font-size: 15px; line-height: 1.65; white-space: pre-line;">{{ $slot }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
