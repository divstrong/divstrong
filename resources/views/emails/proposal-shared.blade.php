<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    {{-- Logo --}}
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <img src="{{ asset('images/logo.png') }}" alt="DivStrong" style="height: 40px;">
                        </td>
                    </tr>

                    {{-- Main Card --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            {{-- Red accent bar --}}
                            <div style="height: 4px; background-color: #ed2537;"></div>

                            <div style="padding: 48px 40px;">
                                <h1 style="color: #111827; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; text-align: center;">
                                    Your Proposal is Ready
                                </h1>

                                <p style="color: #6b7280; font-size: 16px; line-height: 1.6; margin: 0 0 32px 0; text-align: center;">
                                    Hi {{ $proposal->client_name }}, a proposal has been prepared for your review.
                                </p>

                                {{-- Project details --}}
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 12px; margin-bottom: 32px;">
                                    <tr>
                                        <td style="padding: 24px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 4px;">Project</td>
                                                </tr>
                                                <tr>
                                                    <td style="color: #111827; font-size: 18px; font-weight: 600; padding-bottom: 16px;">{{ $proposal->project_title }}</td>
                                                </tr>
                                                @if($proposal->valid_until)
                                                <tr>
                                                    <td style="color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 4px;">Valid Until</td>
                                                </tr>
                                                <tr>
                                                    <td style="color: #374151; font-size: 15px;">{{ $proposal->valid_until->format('F j, Y') }}</td>
                                                </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                @if($notes)
                                <div style="background-color: #fefce8; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 32px;">
                                    <p style="color: #92400e; font-size: 13px; font-weight: 600; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 0.5px;">Note</p>
                                    <p style="color: #78350f; font-size: 15px; line-height: 1.6; margin: 0;">{{ $notes }}</p>
                                </div>
                                @endif

                                {{-- CTA Button --}}
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="padding: 8px 0;">
                                            <a href="{{ $url }}"
                                               style="display: inline-block; background-color: #ed2537; color: #ffffff;
                                                      font-weight: 700; font-size: 16px; text-decoration: none;
                                                      padding: 16px 48px; border-radius: 10px; letter-spacing: 0.5px;">
                                                &#128065;&nbsp;&nbsp;View Proposal
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="color: #9ca3af; font-size: 12px; margin-top: 24px; text-align: center; word-break: break-all;">
                                    Or copy this link: <a href="{{ $url }}" style="color: #ed2537; text-decoration: none;">{{ $url }}</a>
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 32px;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
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
