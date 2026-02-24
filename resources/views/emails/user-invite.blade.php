<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding-bottom: 30px;">
                            <img src="https://www.divstrong.com/images/logo.png"
                                 alt="divStrong"
                                 width="160"
                                 style="display: inline-block; background-color: #ffffff; border-radius: 12px; padding: 12px 20px;" />
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="background-color: #1a1a1a; border: 1px solid #333333; border-radius: 12px; padding: 40px;">
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0 0 16px 0;">You're invited!</h1>

                            <p style="color: #9ca3af; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                Hello {{ $user->name }},
                            </p>

                            <p style="color: #9ca3af; font-size: 16px; line-height: 1.6; margin: 0 0 8px 0;">
                                You've been invited to join <strong style="color: #ffffff;">{{ $companyName }}</strong>. Click the button below to set your password and activate your account.
                            </p>

                            @if($notes)
                            <div style="background-color: #252525; border-left: 3px solid #ed2537; border-radius: 0 8px 8px 0; padding: 16px 20px; margin: 24px 0;">
                                <p style="color: #d1d5db; font-size: 14px; line-height: 1.6; margin: 0; font-style: italic;">
                                    "{{ $notes }}"
                                </p>
                            </div>
                            @endif

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 32px;">
                                <tr>
                                    <td align="center" style="padding: 8px 0;">
                                        <a href="{{ $inviteUrl }}"
                                           style="display: inline-block; background-color: #ed2537; color: #ffffff;
                                                  font-weight: 600; font-size: 16px; text-decoration: none;
                                                  padding: 14px 40px; border-radius: 8px;">
                                            Get Started
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 30px;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">&copy; 2009-{{ date('Y') }} divStrong</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
