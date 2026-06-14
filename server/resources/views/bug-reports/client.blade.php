<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Bug Reports — {{ $site->name }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        .wrap { max-width: 860px; margin: 0 auto; padding: 32px 20px 64px; }
        header { margin-bottom: 24px; }
        header h1 { margin: 0 0 4px; font-size: 22px; font-weight: 700; }
        header p { margin: 0; color: #6b7280; font-size: 14px; }
        .count { color: #6b7280; font-size: 13px; margin: 18px 0 10px; }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .card-top { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 10px; }
        .badge {
            display: inline-flex; align-items: center;
            font-size: 11px; font-weight: 600; letter-spacing: .02em;
            text-transform: uppercase; padding: 3px 9px; border-radius: 999px;
        }
        .b-gray   { background: #f3f4f6; color: #4b5563; }
        .b-info   { background: #dbeafe; color: #1d4ed8; }
        .b-amber  { background: #fef3c7; color: #b45309; }
        .b-red    { background: #fee2e2; color: #b91c1c; }
        .b-green  { background: #dcfce7; color: #15803d; }
        .b-purple { background: #ede9fe; color: #6d28d9; }
        .when { margin-left: auto; color: #9ca3af; font-size: 12px; white-space: nowrap; }
        .what { font-size: 15px; white-space: pre-wrap; word-break: break-word; margin: 0 0 10px; }
        .meta { font-size: 12px; color: #6b7280; word-break: break-all; }
        .meta a { color: #2563eb; text-decoration: none; }
        .meta a:hover { text-decoration: underline; }
        .shot { margin-top: 12px; }
        .shot img {
            max-width: 220px; max-height: 150px;
            border: 1px solid #e5e7eb; border-radius: 6px; display: block;
        }
        details { margin-top: 10px; }
        summary { cursor: pointer; font-size: 12px; color: #6b7280; font-weight: 500; }
        pre {
            white-space: pre-wrap; word-break: break-word;
            background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 6px;
            padding: 10px 12px; font-size: 12px; max-height: 260px; overflow: auto;
            margin: 8px 0 0;
        }
        .empty {
            background: #fff; border: 1px dashed #d1d5db; border-radius: 10px;
            padding: 40px 20px; text-align: center; color: #6b7280;
        }
        footer { margin-top: 32px; text-align: center; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
    @php
        $statusLabels = [
            'new' => ['New', 'b-amber'],
            'triaged' => ['Triaged', 'b-info'],
            'in_progress' => ['In Progress', 'b-purple'],
            'resolved' => ['Resolved', 'b-green'],
            'wontfix' => ["Won't Fix", 'b-gray'],
        ];
        $severityLabels = [
            'critical' => ['Critical', 'b-red'],
            'high' => ['High', 'b-amber'],
            'low' => ['Low', 'b-info'],
            'unsure' => ['Unsure', 'b-gray'],
        ];
    @endphp

    <div class="wrap">
        <header>
            <h1>Bug Reports</h1>
            <p>{{ $site->name }}</p>
        </header>

        @if ($reports->isEmpty())
            <div class="empty">
                No reports have been submitted yet. Reports will appear here as they come in.
            </div>
        @else
            <div class="count">{{ $reports->count() }} {{ Str::plural('report', $reports->count()) }}</div>

            @foreach ($reports as $report)
                @php
                    [$sLabel, $sClass] = $statusLabels[$report->status] ?? [ucfirst($report->status), 'b-gray'];
                    [$vLabel, $vClass] = $severityLabels[$report->severity] ?? [ucfirst((string) $report->severity), 'b-gray'];
                @endphp
                <div class="card">
                    <div class="card-top">
                        <span class="badge {{ $sClass }}">{{ $sLabel }}</span>
                        <span class="badge {{ $vClass }}">Severity: {{ $vLabel }}</span>
                        <span class="when">{{ $report->created_at?->format('M j, Y g:i A') }}</span>
                    </div>

                    <p class="what">{{ $report->what_happened }}</p>

                    <div class="meta">
                        Page: <a href="{{ $report->url }}" target="_blank" rel="noopener noreferrer">{{ $report->url }}</a>
                        @if ($report->reporter_email)
                            <br>Reporter: {{ $report->reporter_email }}
                        @endif
                        @if ($report->viewport_width)
                            <br>Viewport: {{ $report->viewport_width }} × {{ $report->viewport_height }}
                        @endif
                    </div>

                    @if ($report->screenshot_path)
                        <div class="shot">
                            <a href="{{ route('client.bug-reports.screenshot', ['token' => $site->read_token, 'report' => $report->id]) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ route('client.bug-reports.screenshot', ['token' => $site->read_token, 'report' => $report->id]) }}" alt="Screenshot" loading="lazy">
                            </a>
                        </div>
                    @endif

                    @if (! empty($report->console_errors))
                        <details>
                            <summary>Console errors ({{ count($report->console_errors) }})</summary>
                            <pre>{{ implode("\n\n", array_map(fn ($e) => is_string($e) ? $e : json_encode($e), $report->console_errors)) }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        @endif

        <footer>Powered by divStrong</footer>
    </div>
</body>
</html>
