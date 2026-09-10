{{--
    Branded shell for every outreach email.

    $bodyHtml has already been interpolated by EmailTemplate::renderBody() or rendered
    from the mailable's Blade fallback — either way it is trusted, admin-authored HTML.

    Styling matches the rest of divStrong's mail (emails/*.blade.php): dark ground, the
    div/Strong wordmark, one red accent. Everything is inline, because every mail client
    that matters strips <style> from the head.

    $unsubscribeUrl / $postalAddress are passed ONLY by cold outreach (see App\Support\
    Outreach). Transactional mail — proposals, receipts, invites — deliberately leaves
    them unset: those carry no marketing, so the Act does not ask for an opt-out, and
    offering one invites clients to unsubscribe from their own paperwork.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding-bottom: 30px;">
                            <a href="{{ $brandUrl }}" style="text-decoration: none; border: 0;">
                                <span style="font-size: 24px; font-weight: bold; letter-spacing: 2px;">
                                    <span style="color: #ed2537;">div</span><span style="color: #ffffff;">Strong</span>
                                </span>
                            </a>
                        </td>
                    </tr>

                    {{-- Body. The mailable has already dropped the greeting and sign-off
                         into their slots, so this is the finished message. --}}
                    <tr>
                        <td style="background-color: #1a1a1a; border: 1px solid #333333; border-radius: 12px; padding: 40px;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 30px;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0 0 6px 0;">
                                <a href="{{ $brandUrl }}" style="color: #9ca3af; text-decoration: underline;">divstrong.com</a>
                            </p>

                            @if (! empty($postalAddress) || ! empty($unsubscribeUrl))
                                <p style="color: #6b7280; font-size: 12px; line-height: 1.7; margin: 14px 0 0 0;">
                                    @if (! empty($postalAddress))
                                        {{-- The sender's physical postal address. Required by
                                             CAN-SPAM on every commercial message; missing it is
                                             a per-message violation. --}}
                                        {{ $postalAddress }}<br />
                                    @endif

                                    @if (! empty($unsubscribeUrl))
                                        You're getting this because we think divStrong could be useful to your team.
                                        <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af; text-decoration: underline;">Unsubscribe</a>
                                        and we won't email you again.
                                    @endif
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
