<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding-bottom: 30px;">
                            <img src="{{ asset('images/logo.png') }}" alt="divStrong" style="height: 32px;">
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 40px;">
                            <h1 style="color: #111827; font-size: 24px; margin: 0 0 16px 0;">New Appointment Request</h1>

                            <p style="color: #6b7280; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                A new appointment has been requested from the website.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #9ca3af; font-size: 14px;">Name</span><br>
                                        <span style="color: #111827; font-size: 16px; font-weight: 600;">{{ $name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #9ca3af; font-size: 14px;">Email</span><br>
                                        <a href="mailto:{{ $email }}" style="color: #ed2537; font-size: 16px; text-decoration: none;">{{ $email }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #9ca3af; font-size: 14px;">Preferred Date</span><br>
                                        <span style="color: #111827; font-size: 16px; font-weight: 600;">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; {{ $description ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                                        <span style="color: #9ca3af; font-size: 14px;">Preferred Time</span><br>
                                        <span style="color: #111827; font-size: 16px; font-weight: 600;">{{ $time }} EST</span>
                                    </td>
                                </tr>
                                @if($description)
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <span style="color: #9ca3af; font-size: 14px;">Project Description</span><br>
                                        <span style="color: #374151; font-size: 15px; line-height: 1.6;">{{ $description }}</span>
                                    </td>
                                </tr>
                                @endif
                            </table>

                            <p style="color: #9ca3af; font-size: 13px; margin: 0;">
                                Reply directly to this email to respond to <strong style="color: #374151;">{{ $name }}</strong>.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 30px;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} divStrong. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
