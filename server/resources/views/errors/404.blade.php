{{--
    404 page.

    Deliberately self-contained: inline CSS, no Vite, no Alpine, no JS at all. An error page is
    the one page that has to render when something is wrong, and pulling it through the build
    pipeline means a stale or missing manifest turns a missing page into a broken one. It also
    means this page cannot be repainted by an unrelated change to app.css.

    Light ground, so it carries the wordmark as printed — logo.png, the same artwork the site
    and the proposal mail use, whose "Strong" is near-black and needs a pale background to read
    against. The only heavy element is the numerals; everything else is air.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- A 404 has nothing worth indexing, and letting one into the index is how a dead URL
         outranks the live page it replaced. --}}
    <meta name="robots" content="noindex, follow">
    <meta name="color-scheme" content="light">
    <title>404 — Page not found | divStrong</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">

    <style>
        :root {
            --brand: #ed2537;
            --brand-light: #f43f4f;
            --brand-dark: #c91e2e;
            --ink: #0f1115;
            --body: #565e6b;
            --faint: #8b919c;
            --line: #e7e9ec;
            --surface: #f7f8f9;
        }

        * { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            color: var(--body);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Atmosphere, kept to a whisper on a light ground: a warm brand wash behind the
           numerals and a blueprint grid that fades out well before the edges. Both are
           pointer-events:none so they can never swallow a click on the links underneath. */
        .glow,
        .grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        .glow {
            background:
                radial-gradient(55rem 34rem at 50% 34%, rgba(237, 37, 55, 0.07), transparent 70%),
                radial-gradient(38rem 26rem at 88% 4%, rgba(237, 37, 55, 0.05), transparent 70%);
        }

        .grid {
            background-image:
                linear-gradient(rgba(15, 17, 21, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 17, 21, 0.04) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(64rem 46rem at 50% 38%, #000 15%, transparent 72%);
            -webkit-mask-image: radial-gradient(64rem 46rem at 50% 38%, #000 15%, transparent 72%);
        }

        .site-head {
            position: relative;
            padding: 1.75rem clamp(1.25rem, 5vw, 3rem);
        }

        .site-head img {
            width: 132px;
            max-width: 40vw;
            height: auto;
            display: block;
        }

        main {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            /* Matched top and bottom so the block sits optically centred between the
               wordmark and the footer rather than riding high. */
            padding: 1.5rem clamp(1.25rem, 5vw, 3rem) 2rem;
        }

        /* The requested path, echoed back. The one genuinely useful thing a 404 can tell you:
           whether the URL you landed on is the one you meant. */
        .req {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            max-width: 100%;
            margin: 0 0 2rem;
            padding: 0.4rem 0.9rem;
            border: 1px solid var(--line);
            border-radius: 9999px;
            background: var(--surface);
            font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas,
                'Liberation Mono', monospace;
            font-size: 0.8125rem;
            color: var(--faint);
        }

        .req__verb { color: var(--body); font-weight: 600; }

        .req__path {
            color: var(--ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .req__code { color: var(--brand); font-weight: 700; }

        .numerals {
            margin: 0;
            font-size: clamp(5.5rem, 22vw, 12rem);
            font-weight: 900;
            letter-spacing: -0.045em;
            line-height: 0.82;
            /* Solid brand red first so the numerals are never invisible if a browser skips the
               gradient clip below — the whole page hangs on these being legible. */
            color: var(--brand);
            background: linear-gradient(180deg, var(--brand-light) 0%, var(--brand) 52%, var(--brand-dark) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 14px 34px rgba(237, 37, 55, 0.22));
        }

        h1 {
            margin: 1.5rem 0 0;
            font-size: clamp(1.5rem, 4.5vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--ink);
        }

        .lede {
            margin: 0.875rem auto 0;
            max-width: 33rem;
            font-size: clamp(1rem, 2.2vw, 1.125rem);
            line-height: 1.65;
        }

        .actions {
            margin-top: 2.25rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.875rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.75rem;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.25s ease, box-shadow 0.25s ease, color 0.25s ease,
                background-color 0.25s ease, border-color 0.25s ease;
        }

        .btn svg { width: 1.125rem; height: 1.125rem; }

        /* The homepage's own button treatment, kept in step by hand rather than shared: this
           page must not depend on the site stylesheet. */
        .btn--brand {
            background: linear-gradient(to bottom, var(--brand-light) 0%, var(--brand) 40%, var(--brand-dark) 100%);
            border: 1px solid #b91a27;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 6px 18px rgba(237, 37, 55, 0.25);
            color: #ffffff;
        }

        .btn--brand:hover,
        .btn--brand:focus-visible {
            transform: translateY(-1px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 10px 24px rgba(237, 37, 55, 0.32);
        }

        .btn--ghost {
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--ink);
            box-shadow: 0 1px 2px rgba(15, 17, 21, 0.04);
        }

        .btn--ghost:hover,
        .btn--ghost:focus-visible {
            border-color: #c9ced6;
            background: var(--surface);
        }

        .quick {
            margin-top: 3rem;
            padding-top: 1.75rem;
            border-top: 1px solid var(--line);
            width: min(34rem, 100%);
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.5rem 1.5rem;
            font-size: 0.875rem;
        }

        .quick a {
            color: var(--body);
            text-decoration: none;
            padding: 0.25rem 0;
            border-bottom: 1px solid transparent;
            transition: color 0.2s ease, border-color 0.2s ease;
        }

        .quick a:hover,
        .quick a:focus-visible {
            color: var(--ink);
            border-bottom-color: var(--brand);
        }

        .site-foot {
            position: relative;
            padding: 0 clamp(1.25rem, 5vw, 3rem) 2rem;
            text-align: center;
            font-size: 0.8125rem;
            color: var(--faint);
        }

        a:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 3px;
            border-radius: 4px;
        }

        @media (max-width: 30rem) {
            .actions { flex-direction: column; align-items: stretch; }
            .btn { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .btn { transition: none; }
            .btn--brand:hover,
            .btn--brand:focus-visible { transform: none; }
        }
    </style>
</head>
<body>
    <div class="glow" aria-hidden="true"></div>
    <div class="grid" aria-hidden="true"></div>

    <header class="site-head">
        {{-- Served from this app rather than hot-linked from www: an error page should not
             depend on another host answering, and it is the same artwork either way. --}}
        <a href="{{ url('/') }}" aria-label="divStrong home">
            <img src="{{ asset('images/logo.png') }}" alt="divStrong" width="132">
        </a>
    </header>

    <main>
        @php
            // Echoed back to the visitor, so escaping is not optional — the path is whatever
            // they typed. Blade escapes it; the limit stops a pathological URL from stretching
            // the pill off the screen.
            $path = \Illuminate\Support\Str::limit('/'.trim(request()->path(), '/'), 48);
        @endphp

        <p class="req">
            <span class="req__verb">{{ request()->method() }}</span>
            <span class="req__path">{{ $path === '/' ? '/' : $path }}</span>
            <span aria-hidden="true">&rarr;</span>
            <span class="req__code">404</span>
        </p>

        <p class="numerals" aria-hidden="true">404</p>

        <h1>This page didn&rsquo;t ship.</h1>

        <p class="lede">
            Broken link, moved page, or a typo in the URL — nothing you did wrong.
            Everything that <em>did</em> ship is one click away.
        </p>

        <div class="actions">
            <a class="btn btn--brand" href="{{ url('/') }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                </svg>
                Back to home
            </a>

            <a class="btn btn--ghost" href="{{ url('/#work') }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                </svg>
                See our work
            </a>
        </div>

        {{-- The homepage is a single scrolling page, so these are anchors into it rather than
             routes. Kept to the sections a lost visitor is actually looking for. --}}
        <nav class="quick" aria-label="Site sections">
            <a href="{{ url('/#about') }}">About</a>
            <a href="{{ url('/#services') }}">Services</a>
            <a href="{{ url('/#pricing') }}">Pricing</a>
            <a href="{{ url('/#contact') }}">Contact</a>
            <a href="{{ url('/admin') }}">Client Portal</a>
        </nav>
    </main>

    <footer class="site-foot">
        &copy; 2009&ndash;{{ date('Y') }} divStrong &middot; Richmond, VA
    </footer>
</body>
</html>
