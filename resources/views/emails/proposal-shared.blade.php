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
                                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z'/%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'/%3E%3C/svg%3E" alt="" style="vertical-align: middle; margin-right: 8px; display: inline-block;" width="20" height="20">View Proposal
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
