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
                            <span style="font-size: 24px; font-weight: bold; letter-spacing: 2px;">
                                <span style="color: #ed2537;">div</span><span style="color: #ffffff;">Strong</span>
                            </span>
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="background-color: #1a1a1a; border: 1px solid #333333; border-radius: 12px; padding: 40px;">
                            @if($action === 'accepted')
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <span style="display: inline-block; width: 60px; height: 60px; background-color: rgba(16, 185, 129, 0.1); border: 2px solid rgba(16, 185, 129, 0.3); border-radius: 50%; line-height: 56px; font-size: 28px; color: #10b981;">&#10003;</span>
                                </div>
                                <h1 style="color: #10b981; font-size: 24px; margin: 0 0 16px 0; text-align: center;">Proposal Accepted!</h1>
                            @else
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <span style="display: inline-block; width: 60px; height: 60px; background-color: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.3); border-radius: 50%; line-height: 56px; font-size: 28px; color: #ef4444;">&#10005;</span>
                                </div>
                                <h1 style="color: #ef4444; font-size: 24px; margin: 0 0 16px 0; text-align: center;">Proposal Declined</h1>
                            @endif

                            <table width="100%" style="margin-top: 24px;">
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Project:</td>
                                    <td style="color: #ffffff; font-size: 14px; padding: 8px 0; text-align: right;">{{ $proposal->project_title }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Client:</td>
                                    <td style="color: #ffffff; font-size: 14px; padding: 8px 0; text-align: right;">{{ $proposal->client_name }}</td>
                                </tr>
                                @if($action === 'accepted' && $proposal->signature_name)
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Signed by:</td>
                                    <td style="color: #ffffff; font-size: 14px; padding: 8px 0; text-align: right;">{{ $proposal->signature_name }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Accepted at:</td>
                                    <td style="color: #ffffff; font-size: 14px; padding: 8px 0; text-align: right;">{{ $proposal->accepted_at?->format('F j, Y g:i A') }}</td>
                                </tr>
                                @endif
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/admin') }}"
                                           style="display: inline-block; background-color: #ed2537; color: #000000;
                                                  font-weight: 600; font-size: 14px; text-decoration: none;
                                                  padding: 12px 32px; border-radius: 8px;">
                                            View in Admin
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 30px;">
                            <p style="color: #4b5563; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} DivStrong. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
