{{--
    Public opt-out page.

    Deliberately plain and self-contained: no Vite bundle, no Livewire, no session. It has to
    render for someone who arrives from a mail client months after the email was sent, on a
    server that may have nothing else warm.

    No tracking of any kind on this page. Someone asking to be left alone should not be
    measured on the way out.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $done ? 'Unsubscribed' : 'Unsubscribe' }} — divStrong</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a0a;
            color: #9ca3af;
            font: 16px/1.6 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding: 24px;
        }
        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 14px;
            max-width: 480px;
            width: 100%;
            padding: 40px 36px;
            text-align: center;
        }
        .wordmark {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            display: block;
            margin-bottom: 28px;
        }
        .wordmark .div { color: #ed2537; }
        .wordmark .strong { color: #fff; }
        h1 { font-size: 21px; color: #fff; margin: 0 0 12px; }
        p { margin: 0 0 18px; }
        strong { color: #e5e7eb; }
        .note { font-size: 13px; color: #6b7280; }
        button {
            background: #ed2537;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 13px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #c91e2e; }
        .tick { font-size: 40px; line-height: 1; margin-bottom: 10px; color: #22c55e; }
        .foot { margin-top: 28px; font-size: 13px; color: #6b7280; }
        .foot a { color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <span class="wordmark"><span class="div">div</span><span class="strong">Strong</span></span>

        @if ($done)
            <div class="tick">&#10003;</div>
            <h1>You're unsubscribed</h1>
            <p>
                @if ($prospect->email)
                    We won't email <strong>{{ $prospect->email }}</strong> again.
                @else
                    We won't email you again.
                @endif
            </p>
            {{-- Honest about the edge case rather than promising an instant stop we cannot
                 guarantee for mail already handed to the provider. --}}
            <p class="note">Anything already on its way may still arrive in the next day or so.</p>
        @else
            <h1>Unsubscribe from divStrong emails</h1>
            <p>
                @if ($prospect->email)
                    Confirm and we'll stop emailing <strong>{{ $prospect->email }}</strong>.
                @else
                    Confirm and we'll stop emailing you.
                @endif
            </p>

            {{-- Signed URL, so no CSRF token is needed or available here — this page is
                 reached without a session. --}}
            <form method="POST" action="{{ $actionUrl }}">
                <button type="submit">Unsubscribe me</button>
            </form>
        @endif

        <div class="foot">
            <a href="https://divstrong.com">divstrong.com</a>
            @if ($address = \App\Support\Outreach::postalAddress())
                <br>{{ $address }}
            @endif
        </div>
    </div>
</body>
</html>
