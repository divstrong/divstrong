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
                            <div style="text-align: center; margin-bottom: 20px;">
                                <span style="display: inline-block; width: 60px; height: 60px; background-color: #ecfdf5; border: 2px solid #a7f3d0; border-radius: 50%; line-height: 56px; font-size: 28px; color: #10b981;">&#36;</span>
                            </div>
                            <h1 style="color: #059669; font-size: 24px; margin: 0 0 16px 0; text-align: center;">Payment Received!</h1>

                            <table width="100%" style="margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Project:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->project_title }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Client:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $proposal->client_name }}</td>
                                </tr>
                                @if($payment->milestone)
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Milestone:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $payment->milestone->title }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Amount:</td>
                                    <td style="color: #059669; font-size: 18px; padding: 8px 0; text-align: right; font-weight: 700;">${{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                                </tr>
                                @if($payment->payer_email)
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Payer email:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $payment->payer_email }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Capture ID:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $payment->paypal_capture_id }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280; font-size: 14px; padding: 8px 0;">Paid at:</td>
                                    <td style="color: #111827; font-size: 14px; padding: 8px 0; text-align: right; font-weight: 500;">{{ $payment->paid_at->format('F j, Y g:i A') }}</td>
                                </tr>
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
                            <p style="color: #9ca3af; font-size: 12px;">&copy; 2009-{{ date('Y') }} divStrong</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
