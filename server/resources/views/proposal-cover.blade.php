<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $proposal->project_title ?: 'Proposal' }} — Cover</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    @page { size: letter portrait; margin: 0; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    html, body { margin: 0; padding: 0; background: #f3f4f6; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }

    .page {
        width: 8.5in;
        height: 11in;
        margin: 0 auto;
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    /* Hero / cover image */
    .hero {
        position: relative;
        height: 3.8in;
        background: #111827 center/cover no-repeat;
        flex-shrink: 0;
    }
    .hero::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(17,24,39,0.35) 0%, rgba(17,24,39,0.75) 100%);
    }
    .hero-inner {
        position: relative; z-index: 1;
        height: 100%;
        padding: 0.6in 0.75in;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        color: #fff;
    }
    .kicker {
        font-size: 10pt;
        letter-spacing: 4pt;
        text-transform: uppercase;
        color: rgba(255,255,255,0.7);
        margin-bottom: 14pt;
    }
    .company {
        font-size: 32pt;
        font-weight: 800;
        line-height: 1.1;
        margin: 0 0 6pt 0;
        letter-spacing: -0.5pt;
    }
    .project {
        font-size: 16pt;
        font-weight: 500;
        color: rgba(255,255,255,0.85);
        margin: 0;
    }

    /* Meta strip */
    .meta {
        display: flex;
        gap: 0.4in;
        padding: 0.3in 0.75in;
        border-bottom: 1px solid #e5e7eb;
        flex-shrink: 0;
    }
    .meta-item { flex: 1; }
    .meta-label {
        font-size: 8pt;
        letter-spacing: 2pt;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 4pt;
    }
    .meta-value {
        font-size: 12pt;
        font-weight: 600;
        color: #111827;
    }

    /* QR section */
    .qr-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0.4in 0.75in;
        text-align: center;
    }
    .qr-message {
        max-width: 5.5in;
        font-size: 14pt;
        line-height: 1.5;
        color: #374151;
        margin: 0 0 0.35in 0;
    }
    .qr-message strong { color: #111827; }
    .qr-box {
        width: 2.6in;
        height: 2.6in;
        padding: 0.18in;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14pt;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .qr-box svg { width: 100%; height: 100%; display: block; }
    .qr-url {
        margin-top: 0.2in;
        font-size: 9pt;
        color: #6b7280;
        letter-spacing: 0.5pt;
        word-break: break-all;
    }

    /* Footer */
    .footer {
        flex-shrink: 0;
        padding: 0.3in 0.75in 0.5in;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #e5e7eb;
    }
    .footer img { height: 24pt; width: auto; }
    .footer .footer-meta {
        font-size: 8.5pt;
        color: #9ca3af;
        text-align: right;
    }

    /* Print toolbar (hidden when printing) */
    .toolbar {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 100;
        display: flex;
        gap: 8px;
    }
    .toolbar button {
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #111827;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .toolbar button.primary {
        background: #ed2537;
        color: #fff;
        border-color: #ed2537;
    }
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
        <button onclick="window.print()" class="primary">Print / Save as PDF</button>
    </div>

    <div class="page">
        <div class="hero" @if($proposal->cover_image) style="background-image: url('{{ Storage::url($proposal->cover_image) }}');" @endif>
            <div class="hero-inner">
                <div class="kicker">Proposal</div>
                <h1 class="company">{{ $proposal->client_company ?: $proposal->client_name ?: 'Client' }}</h1>
                @if($proposal->project_title)
                    <p class="project">{{ $proposal->project_title }}</p>
                @endif
            </div>
        </div>

        <div class="meta">
            @if($proposal->client_name)
                <div class="meta-item">
                    <div class="meta-label">Prepared For</div>
                    <div class="meta-value">{{ $proposal->client_name }}</div>
                </div>
            @endif
            @if($proposal->proposal_date)
                <div class="meta-item">
                    <div class="meta-label">Date</div>
                    <div class="meta-value">{{ $proposal->proposal_date->format('F j, Y') }}</div>
                </div>
            @endif
            @if($proposal->rfp_number)
                <div class="meta-item">
                    <div class="meta-label">RFP</div>
                    <div class="meta-value">{{ $proposal->rfp_number }}</div>
                </div>
            @endif
        </div>

        <div class="qr-section">
            <p class="qr-message">
                <strong>Scan to view this proposal online</strong><br>
                in an interactive format for an enhanced narrative, supporting media, and full project details.
            </p>
            <div class="qr-box">
                {!! $qr !!}
            </div>
            <div class="qr-url">{{ $proposal->public_url }}</div>
        </div>

        <div class="footer">
            <img src="{{ asset('images/logo.png') }}" alt="divStrong">
            <div class="footer-meta">
                Prepared by divStrong &middot; divstrong.com
            </div>
        </div>
    </div>
</body>
</html>
