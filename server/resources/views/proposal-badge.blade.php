<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $proposal->client_company ?: $proposal->client_name ?: 'Proposal' }} — Badge</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    @page { size: letter portrait; margin: 0; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body {
        margin: 0; padding: 0;
        background: #f3f4f6;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #1f2937;
    }

    .page {
        width: 8.5in;
        height: 11in;
        margin: 0 auto;
        background: #111827 linear-gradient(150deg, #1f2937 0%, #111827 55%, #0b1220 100%);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Full-bleed cover art (an <img> prints without needing "background graphics") */
    .backdrop { position: absolute; inset: 0; }
    .backdrop img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .backdrop::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(160deg, rgba(17,24,39,0.72) 0%, rgba(17,24,39,0.55) 45%, rgba(17,24,39,0.85) 100%);
    }

    /* The badge itself */
    .badge {
        position: relative;
        z-index: 1;
        width: 5.9in;
        background: #fff;
        border-radius: 22pt;
        overflow: hidden;
        box-shadow: 0 24pt 60pt rgba(0,0,0,0.45);
        padding: 0.55in 0.55in 0.45in;
        text-align: center;
    }
    .badge::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 7pt;
        background: #ed2537;
        border-radius: 22pt 22pt 0 0;
    }

    .kicker {
        font-size: 9pt;
        letter-spacing: 5pt;
        text-transform: uppercase;
        color: #ed2537;
        font-weight: 700;
        margin: 6pt 0 14pt;
    }
    .client {
        font-size: 30pt;
        font-weight: 800;
        line-height: 1.12;
        letter-spacing: -0.6pt;
        color: #111827;
        margin: 0;
    }
    .project {
        font-size: 12.5pt;
        font-weight: 500;
        color: #6b7280;
        margin: 10pt 0 0;
        line-height: 1.4;
    }
    .rule {
        width: 46pt;
        height: 2pt;
        background: #e5e7eb;
        border-radius: 2pt;
        margin: 22pt auto;
    }

    .qr-box {
        width: 3.1in;
        height: 3.1in;
        margin: 0 auto;
        padding: 0.16in;
        background: #fff;
        border: 1.5pt solid #e5e7eb;
        border-radius: 16pt;
    }
    .qr-box svg { width: 100%; height: 100%; display: block; }

    .scan {
        font-size: 12pt;
        font-weight: 600;
        color: #111827;
        margin: 18pt 0 5pt;
    }
    .url {
        font-size: 9.5pt;
        color: #9ca3af;
        letter-spacing: 0.4pt;
        word-break: break-all;
        margin: 0;
    }

    .badge-footer {
        margin-top: 26pt;
        padding-top: 16pt;
        border-top: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .badge-footer img { height: 22pt; width: auto; }

    /* Screen-only toolbar */
    .toolbar {
        position: fixed;
        top: 16px; right: 16px;
        z-index: 100;
        display: flex;
        gap: 8px;
    }
    .toolbar button, .toolbar a {
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #111827;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .toolbar button.primary { background: #ed2537; color: #fff; border-color: #ed2537; }

    @media print {
        body { background: #fff; }
        .toolbar { display: none !important; }
        .page { box-shadow: none; margin: 0; }
    }
    @media screen {
        body { padding: 24px 0; }
        .page { box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('proposal.view', $proposal->uuid) }}">&larr; Proposal</a>
        <button onclick="window.print()" class="primary">Print / Save as PDF</button>
    </div>

    <div class="page">
        @if($proposal->cover_image)
            <div class="backdrop">
                <img src="{{ Storage::url($proposal->cover_image) }}" alt="" onerror="this.closest('.backdrop').remove()">
            </div>
        @endif

        <div class="badge">
            <div class="kicker">Proposal</div>

            <h1 class="client">{{ $proposal->client_company ?: $proposal->client_name ?: 'Client' }}</h1>

            @if($proposal->project_title)
                <p class="project">{{ $proposal->project_title }}</p>
            @endif

            <div class="rule"></div>

            <div class="qr-box">{!! $qr !!}</div>

            <p class="scan">Scan to view this proposal online</p>
            <p class="url">{{ $proposal->public_url }}</p>

            <div class="badge-footer">
                <img src="{{ asset('images/logo.png') }}" alt="divStrong">
            </div>
        </div>
    </div>
</body>
</html>
