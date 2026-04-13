<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $proposal->project_title ?: 'Proposal' }}</title>
<style>
    @page { margin: 0.6in 0.65in; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #1f2937;
        font-size: 10.5pt;
        line-height: 1.55;
        margin: 0;
    }
    h1, h2, h3, h4 { color: #111827; margin: 0; font-weight: 700; }
    p { margin: 0 0 8pt 0; }
    a { color: #ed2537; text-decoration: none; }

    /* Cover */
    .cover {
        background: #111827;
        color: #fff;
        padding: 60pt 40pt;
        margin: -0.6in -0.65in 24pt -0.65in;
        text-align: center;
        page-break-after: always;
    }
    .cover .kicker {
        color: rgba(255,255,255,0.55);
        font-size: 8pt;
        letter-spacing: 4pt;
        text-transform: uppercase;
        margin-bottom: 24pt;
    }
    .cover .company {
        font-size: 36pt;
        color: #fff;
        margin-bottom: 10pt;
        line-height: 1.1;
    }
    .cover .project {
        font-size: 14pt;
        color: rgba(255,255,255,0.8);
        font-weight: 400;
        margin-bottom: 28pt;
    }
    .cover .date {
        font-size: 10pt;
        color: rgba(255,255,255,0.5);
        letter-spacing: 1pt;
    }

    /* Sections */
    .section { margin-bottom: 24pt; page-break-inside: avoid; }
    .section-title {
        font-size: 9pt;
        color: #ed2537;
        letter-spacing: 3pt;
        text-transform: uppercase;
        font-weight: 700;
        border-bottom: 2pt solid #ed2537;
        padding-bottom: 6pt;
        margin-bottom: 14pt;
    }
    .section h2 { font-size: 18pt; margin-bottom: 10pt; }

    /* Meta table */
    .meta { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .meta td { padding: 6pt 10pt; font-size: 10pt; background: #f9fafb; border-bottom: 1pt solid #f3f4f6; }
    .meta td.label { color: #6b7280; font-weight: 600; width: 28%; text-transform: uppercase; font-size: 8.5pt; letter-spacing: 0.5pt; }
    .meta td.value { color: #111827; }

    /* Scope */
    .scope-item { margin-bottom: 14pt; padding: 12pt 14pt; background: #f9fafb; border-left: 3pt solid #ed2537; page-break-inside: avoid; }
    .scope-item .cat { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5pt; font-weight: 700; margin-bottom: 4pt; }
    .scope-item .title { font-size: 12pt; font-weight: 700; color: #111827; margin-bottom: 5pt; }
    .scope-item .desc { color: #4b5563; margin-bottom: 6pt; }
    .scope-item ul { margin: 4pt 0 0 18pt; padding: 0; color: #374151; }
    .scope-item li { margin-bottom: 3pt; }

    /* Investment table */
    .cost-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .cost-table th {
        background: #111827; color: #fff; padding: 10pt 12pt; text-align: left;
        font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; font-weight: 700;
    }
    .cost-table th.num, .cost-table td.num { text-align: right; }
    .cost-table td { padding: 10pt 12pt; border-bottom: 1pt solid #e5e7eb; vertical-align: top; font-size: 10pt; }
    .cost-table tr:nth-child(even) td { background: #f9fafb; }
    .totals { width: 100%; margin-top: 10pt; }
    .totals td { padding: 5pt 12pt; font-size: 10pt; }
    .totals td.label { text-align: right; color: #6b7280; width: 70%; }
    .totals td.value { text-align: right; color: #111827; font-weight: 700; width: 30%; }
    .totals tr.total td { font-size: 14pt; border-top: 2pt solid #111827; padding-top: 10pt; padding-bottom: 10pt; color: #ed2537; }

    /* Milestones */
    .milestone { padding: 10pt 12pt; background: #f9fafb; margin-bottom: 8pt; border-radius: 3pt; page-break-inside: avoid; }
    .milestone .row { display: block; }
    .milestone .mtitle { font-weight: 700; font-size: 11pt; color: #111827; }
    .milestone .mdue { color: #6b7280; font-size: 9pt; margin-top: 2pt; }
    .milestone .mdesc { color: #4b5563; font-size: 10pt; margin-top: 4pt; }
    .milestone .mamount { float: right; font-weight: 700; color: #ed2537; font-size: 12pt; }

    /* Roadmap */
    .phase { padding: 10pt 12pt; background: #f9fafb; margin-bottom: 8pt; border-left: 3pt solid #6b7280; page-break-inside: avoid; }
    .phase .ptitle { font-weight: 700; color: #111827; }
    .phase .pdesc { color: #4b5563; font-size: 10pt; margin-top: 4pt; }

    /* Terms */
    .terms-content { color: #374151; font-size: 10pt; line-height: 1.6; }
    .terms-content p { margin-bottom: 8pt; }

    /* Footer */
    .footer {
        text-align: center; color: #9ca3af; font-size: 8.5pt;
        border-top: 1pt solid #e5e7eb; padding-top: 10pt; margin-top: 30pt;
    }

    .html-content p { margin: 0 0 8pt 0; }
    .html-content ul, .html-content ol { margin: 0 0 8pt 18pt; padding: 0; }
    .html-content strong { font-weight: 700; color: #111827; }
</style>
</head>
<body>

{{-- ========= COVER ========= --}}
<div class="cover">
    <div class="kicker">Digital Services Proposal</div>
    <div class="company">{{ $proposal->client_company ?: $proposal->client_name ?: 'Proposal' }}</div>
    @if($proposal->project_title)
        <div class="project">{{ $proposal->project_title }}</div>
    @endif
    @if($proposal->proposal_date)
        <div class="date">{{ $proposal->proposal_date->format('F j, Y') }}</div>
    @endif
</div>

{{-- ========= META ========= --}}
<table class="meta">
    @if($proposal->client_name)
        <tr><td class="label">Prepared For</td><td class="value">{{ $proposal->client_name }}@if($proposal->client_email) &middot; {{ $proposal->client_email }}@endif</td></tr>
    @endif
    @if($proposal->client_company)
        <tr><td class="label">Organization</td><td class="value">{{ $proposal->client_company }}@if($proposal->client_domain) &middot; {{ $proposal->client_domain }}@endif</td></tr>
    @endif
    @if($proposal->proposal_date)
        <tr><td class="label">Proposal Date</td><td class="value">{{ $proposal->proposal_date->format('F j, Y') }}</td></tr>
    @endif
    @if($proposal->valid_until)
        <tr><td class="label">Valid Until</td><td class="value">{{ $proposal->valid_until->format('F j, Y') }}</td></tr>
    @endif
</table>

{{-- ========= OVERVIEW ========= --}}
@if($proposal->introduction)
    <div class="section">
        <div class="section-title">Overview</div>
        <div class="html-content">{!! $proposal->introduction !!}</div>
    </div>
@endif

{{-- ========= ROADMAP ========= --}}
@if($proposal->roadmap_enabled && $proposal->roadmapPhases->count())
    <div class="section">
        <div class="section-title">Roadmap</div>
        @if($proposal->roadmap_title)<h2>{{ $proposal->roadmap_title }}</h2>@endif
        @if($proposal->roadmap_subtitle)<p style="color:#6b7280; margin-bottom:12pt;">{{ $proposal->roadmap_subtitle }}</p>@endif
        @foreach($proposal->roadmapPhases as $phase)
            <div class="phase">
                <div class="ptitle">{{ $phase->title ?? 'Phase ' . $loop->iteration }}</div>
                @if(!empty($phase->description))
                    <div class="pdesc">{!! nl2br(e($phase->description)) !!}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- ========= SCOPE ========= --}}
@if($proposal->scopeItems->count())
    <div class="section">
        <div class="section-title">Scope of Work</div>
        @foreach($proposal->scopeItems as $item)
            <div class="scope-item">
                @if($item->category)<div class="cat">{{ $item->category }}</div>@endif
                <div class="title">{{ $item->title }}</div>
                @if($item->description)<div class="desc">{!! nl2br(e($item->description)) !!}</div>@endif
                @if(is_array($item->bullets) && count($item->bullets))
                    <ul>
                        @foreach($item->bullets as $bullet)
                            <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- ========= INVESTMENT ========= --}}
@if($proposal->costItems->count())
    <div class="section">
        <div class="section-title">Investment</div>
        <table class="cost-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="num" style="width:70pt;">Qty</th>
                    <th class="num" style="width:90pt;">Unit</th>
                    <th class="num" style="width:100pt;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposal->costItems as $cost)
                    <tr>
                        <td>{{ $cost->description }}</td>
                        <td class="num">{{ $cost->quantity }}</td>
                        <td class="num">${{ number_format((float) $cost->unit_price, 2) }}</td>
                        <td class="num">${{ number_format((float) $cost->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">${{ number_format($proposal->subtotal, 2) }}</td>
            </tr>
            @if($proposal->discount_enabled && $proposal->discount_amount > 0)
                <tr>
                    <td class="label">Discount
                        @if($proposal->discount_type === 'percent')
                            ({{ rtrim(rtrim(number_format((float) $proposal->discount_value, 2), '0'), '.') }}%)
                        @endif
                    </td>
                    <td class="value">&minus; ${{ number_format($proposal->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total">
                <td class="label">Total</td>
                <td class="value">${{ number_format($proposal->total, 2) }}</td>
            </tr>
        </table>

        @if($proposal->cost_notes)
            <p style="color:#6b7280; font-size:9.5pt; margin-top:12pt; font-style:italic;">{!! nl2br(e($proposal->cost_notes)) !!}</p>
        @endif
    </div>
@endif

{{-- ========= MILESTONES ========= --}}
@if($proposal->milestones->count())
    <div class="section">
        <div class="section-title">Payment Schedule</div>
        @foreach($proposal->milestones as $ms)
            <div class="milestone">
                @if($ms->amount)
                    <span class="mamount">${{ number_format((float) $ms->amount, 2) }}</span>
                @endif
                <div class="mtitle">{{ $ms->title }}</div>
                @if($ms->due_description)
                    <div class="mdue">{{ $ms->due_description }}</div>
                @endif
                @if($ms->description)
                    <div class="mdesc">{!! nl2br(e($ms->description)) !!}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- ========= CHANGE REQUEST ========= --}}
@if($proposal->change_request_content)
    <div class="section">
        <div class="section-title">Change Requests</div>
        <div class="html-content">{!! $proposal->change_request_content !!}</div>
    </div>
@endif

{{-- ========= TERMS ========= --}}
@if($proposal->terms->count())
    <div class="section">
        <div class="section-title">Terms &amp; Conditions</div>
        <div class="terms-content">
            @foreach($proposal->terms as $term)
                <div class="html-content" style="margin-bottom: 10pt;">{!! $term->content !!}</div>
            @endforeach
        </div>
    </div>
@endif

{{-- ========= FOOTER ========= --}}
<div class="footer">
    &copy; {{ now()->year }} DivStrong &middot; View online: {{ $proposal->public_url }}
</div>

</body>
</html>
