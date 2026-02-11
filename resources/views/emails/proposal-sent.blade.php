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
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0 0 16px 0;">You've received a proposal</h1>

                            <p style="color: #9ca3af; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                Hello {{ $proposal->client_name }},
                            </p>

                            <p style="color: #9ca3af; font-size: 16px; line-height: 1.6; margin: 0 0 8px 0;">
                                We've prepared a proposal for <strong style="color: #ffffff;">{{ $proposal->project_title }}</strong> for your review.
                            </p>

                            @if($proposal->valid_until)
                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0 0 32px 0;">
                                This proposal is valid until {{ $proposal->valid_until->format('F j, Y') }}.
                            </p>
                            @endif

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0;">
                                        <a href="{{ $url }}"
                                           style="display: inline-block; background-color: #ed2537; color: #000000;
                                                  font-weight: 600; font-size: 16px; text-decoration: none;
                                                  padding: 14px 40px; border-radius: 8px;">
                                            View Proposal
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #6b7280; font-size: 12px; margin-top: 24px; text-align: center;">
                                Or copy this link: {{ $url }}
                            </p>
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
