{{--
    The shell every divStrong transactional email sits in.

    White card on a light ground, the wordmark above it, one red accent. Anything that
    needs to look the same in every message — background, logo, card, footer, the
    responsive rules — lives here and nowhere else, so a change lands everywhere at once.

    Cold outreach deliberately keeps its own dark shell (emails/outreach/shell.blade.php):
    its bodies are admin-authored HTML written against a dark ground.

    Slots: $slot (card body), $footer (extra line under the card).
--}}
@props([
    'preheader' => null,
    'title' => null,
    'footer' => null,
])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a { color: #ed2537; }

        @media screen and (max-width: 620px) {
            .sm-full { width: 100% !important; max-width: 100% !important; }
            .sm-pad { padding-left: 24px !important; padding-right: 24px !important; }
            .sm-pad-y { padding-top: 32px !important; padding-bottom: 32px !important; }
            .sm-h1 { font-size: 26px !important; line-height: 1.25 !important; }
            .sm-stack { display: block !important; width: 100% !important; text-align: left !important; }
            .sm-stack-label { padding-bottom: 2px !important; }
            .sm-btn-wrap { width: 100% !important; }
            .sm-btn { display: block !important; padding-left: 16px !important; padding-right: 16px !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    @if($preheader)
        {{-- Shown in the inbox preview line, hidden in the body. The zero-width joiners
             stop the client from padding the preview out with the first line of copy. --}}
        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all;">
            {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f1f2f4;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="sm-full" style="width: 600px; max-width: 600px;">

                    {{-- Wordmark --}}
                    <tr>
                        <td align="center" style="padding-bottom: 28px;">
                            <a href="https://www.divstrong.com" style="text-decoration: none;">
                                <img src="https://www.divstrong.com/images/logo.png"
                                     alt="divStrong"
                                     width="132"
                                     style="display: block; width: 132px; max-width: 132px; height: auto;">
                            </a>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 16px; border: 1px solid #e4e6ea; overflow: hidden;">

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td height="4" style="height: 4px; line-height: 4px; font-size: 0; background-color: #ed2537;">&nbsp;</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="sm-pad sm-pad-y" style="padding: 44px 48px;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 28px 24px 0 24px;">
                            <p style="margin: 0 0 6px 0; font-size: 12px; line-height: 1.6;">
                                <a href="https://www.divstrong.com" style="color: #6b7280; text-decoration: none; font-weight: 600;">divstrong.com</a>
                            </p>
                            <p style="margin: 0; color: #a8adb6; font-size: 12px; line-height: 1.6;">
                                &copy; 2009&ndash;{{ date('Y') }} divStrong.@if($footer) {{ $footer }}@endif
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
