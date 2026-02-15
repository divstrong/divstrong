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
                            <img src="{{ asset('images/logo.png') }}" alt="DivStrong" style="height: 40px;">
                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 40px;">
                            @if(in_array($action, ['accepted', 'converted']))
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <span style="display: inline-block; width: 60px; height: 60px; background-color: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 50%; line-height: 56px; font-size: 28px; color: #10b981;">&#10003;</span>
                                </div>
                                <h1 style="color: #059669; font-size: 24px; margin: 0 0 16px 0; text-align: center;">Proposal Approved!</h1>
                            @else
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <span style="display: inline-block; width: 60px; height: 60px; background-color: #fef2f2; border: 2px solid #fecaca; border-radius: 50%; line-height: 56px; font-size: 28px; color: #ef4444;">&#10005;</span>
                                </div>
                                <h1 style="color: #dc2626; font-size: 24px; margin: 0 0 16px 0; text-align: center;">Proposal Declined</h1>
                            @endif

                            <table width="100%" style="margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Project:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->project_title }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Client:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->client_name }}</td>
                                </tr>
                                @if(in_array($action, ['accepted', 'converted']))
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Signed by:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->tc_signature_name ?? $proposal->signature_name }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Approved at:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->accepted_at?->format('F j, Y g:i A') }}</td>
                                </tr>
                                @endif
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/admin') }}"
                                           style="display: inline-block; background-color: #ed2537; color: #ffffff;
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
            <p class="text-sm text-gray-400">&copy; 2009-{{ date('Y') }} divStrong</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
