<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding-bottom: 30px;">
                            <img src="https://www.divstrong.com/images/logo.png" alt="divStrong" style="height: 40px; width: auto;">
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e4e4e7; border-radius: 12px; padding: 40px;">
                            <h1 style="color: #18181b; font-size: 24px; margin: 0 0 16px 0;">Scanna Analysis Complete</h1>

                            <p style="color: #71717a; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                A new RFP has been screened and is ready for review.
                            </p>

                            {{-- RFP Details Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <span style="color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">RFP Title</span><br>
                                                    <span style="color: #18181b; font-size: 16px; font-weight: 600;">{{ $rfpScreen->rfp_name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <span style="color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Fit Score</span><br>
                                                    <span style="font-size: 28px; font-weight: 700; color: {{ $rfpScreen->score >= 70 ? '#16a34a' : ($rfpScreen->score >= 40 ? '#ca8a04' : '#dc2626') }};">{{ $rfpScreen->score }}/100</span>
                                                    <span style="color: {{ $rfpScreen->score >= 70 ? '#16a34a' : ($rfpScreen->score >= 40 ? '#ca8a04' : '#dc2626') }}; font-size: 14px; margin-left: 8px;">{{ $rfpScreen->score_label }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <span style="color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Summary</span><br>
                                                    <span style="color: #3f3f46; font-size: 14px; line-height: 1.5;">{{ Str::limit($rfpScreen->summary, 200) }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 8px 0;">
                                        <a href="{{ $url }}"
                                           style="display: inline-block; background-color: #ed2537; color: #ffffff;
                                                  font-weight: 600; font-size: 16px; text-decoration: none;
                                                  padding: 14px 40px; border-radius: 8px;">
                                            View Results
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #a1a1aa; font-size: 12px; margin-top: 24px; text-align: center;">
                                Or copy this link: {{ $url }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="text-align: center; padding-top: 30px;">
                            <p style="color: #a1a1aa; font-size: 12px;">&copy; 2009-{{ date('Y') }} divStrong</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
