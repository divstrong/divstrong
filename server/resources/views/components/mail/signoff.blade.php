{{-- Closing line above a hairline, with a person's name on it where there is one. --}}
@props(['name' => null, 'email' => null, 'space' => 36])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    @if($space)
    <tr><td style="height: {{ $space }}px; line-height: {{ $space }}px; font-size: 0;">&nbsp;</td></tr>
    @endif
    <tr>
        <td style="border-top: 1px solid #ebedf0; padding-top: 28px;">
            @if(trim($slot) !== '')
            <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.65;">{{ $slot }}</p>
            @endif
            @if($name)
            <p style="margin: 14px 0 0 0; color: #0f1115; font-size: 15px; font-weight: 600; line-height: 1.5;">{{ $name }}</p>
            @if($email)
            <p style="margin: 2px 0 0 0; color: #8b919c; font-size: 13px; line-height: 1.5;">
                divStrong &middot; <a href="mailto:{{ $email }}" style="color: #8b919c; text-decoration: none;">{{ $email }}</a>
            </p>
            @endif
            @endif
        </td>
    </tr>
</table>
