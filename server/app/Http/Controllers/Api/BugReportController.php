<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\BugReporterSite;
use App\Notifications\NewBugReportNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BugReportController extends Controller
{
    public function preflight(Request $request): Response
    {
        return $this->withCors(response('', 204), $request, null);
    }

    /**
     * Tells the embed widget whether it should render at all.
     *
     * The is_active toggle is stored server-side, but bug-reporter.js is a
     * static file, so without this the widget draws its icon and form on every
     * page and only discovers the site is switched off when store() rejects
     * the submission. The key is passed as a query param rather than the
     * X-Site-Key header so this stays a CORS "simple request" and skips the
     * preflight round-trip on every page load.
     */
    public function config(Request $request): JsonResponse
    {
        $key = $request->query('key') ?? $request->header('X-Site-Key');

        $site = $key
            ? BugReporterSite::where('public_key', $key)->first()
            : null;

        $response = response()->json([
            'active' => (bool) $site?->is_active,
        ]);

        // Short private cache: keeps repeat page loads off the database while
        // still letting an admin toggle take effect within a minute. Private
        // because the CORS headers vary by origin.
        $response->headers->set('Cache-Control', 'private, max-age=60');

        return $this->withCors($response, $request, $site);
    }

    public function store(Request $request): JsonResponse|Response
    {
        $key = $request->header('X-Site-Key') ?? $request->input('site_key');

        $site = $key
            ? BugReporterSite::where('public_key', $key)->where('is_active', true)->first()
            : null;

        if (! $site) {
            return $this->withCors(response()->json(['message' => 'Invalid or inactive site key.'], 401), $request, null);
        }

        $origin = $request->header('Origin') ?? $request->header('Referer');

        if (! $site->matchesOrigin($origin)) {
            return $this->withCors(response()->json([
                'message' => 'Origin does not match this site.',
            ], 403), $request, $site);
        }

        $data = $request->validate([
            'what_happened' => ['required', 'string', 'max:5000'],
            'url' => ['required', 'string', 'max:2048'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'severity' => ['nullable', 'string', 'in:'.implode(',', \App\Models\BugReport::SEVERITIES)],
            'viewport_width' => ['nullable', 'integer', 'between:0,20000'],
            'viewport_height' => ['nullable', 'integer', 'between:0,20000'],
            'console_errors' => ['nullable', 'array'],
            'console_errors.*' => ['string', 'max:2000'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            // The 'local' disk is configured with throw => false, so a write
            // failure (e.g. storage/ not writable on the server) returns false
            // rather than throwing. Normalise that to null so the report still
            // saves cleanly without a screenshot instead of losing the report.
            $stored = $request->file('screenshot')->store(
                'bug-screenshots/'.$site->id,
                'local'
            );
            $screenshotPath = is_string($stored) && $stored !== '' ? $stored : null;
        }

        $report = BugReport::create([
            'bug_reporter_site_id' => $site->id,
            'what_happened' => $data['what_happened'],
            'url' => $data['url'],
            'reporter_email' => $data['reporter_email'] ?? null,
            'user_agent' => Str::limit((string) $request->header('User-Agent'), 1023, ''),
            'viewport_width' => $data['viewport_width'] ?? null,
            'viewport_height' => $data['viewport_height'] ?? null,
            'console_errors' => $data['console_errors'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'screenshot_path' => $screenshotPath,
            'status' => 'new',
            'priority' => 'normal',
            'severity' => $data['severity'] ?? 'unsure',
            'ip_address' => $request->ip(),
            'meta' => $data['meta'] ?? null,
        ]);

        $this->dispatchNotifications($site, $report);

        return $this->withCors(response()->json([
            'data' => [
                'id' => $report->id,
                'received_at' => $report->created_at?->toIso8601String(),
            ],
        ], 201), $request, $site);
    }

    private function dispatchNotifications(BugReporterSite $site, BugReport $report): void
    {
        $recipients = [];

        if ($site->notify_email) {
            $recipients[] = $site->notify_email;
        } elseif ($site->client?->email) {
            $recipients[] = $site->client->email;
        }

        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $email) {
            Notification::route('mail', $email)->notify(new NewBugReportNotification($report));
        }
    }

    private function withCors(Response|JsonResponse $response, Request $request, ?BugReporterSite $site): Response|JsonResponse
    {
        $origin = $request->header('Origin');

        if ($origin && $site && $site->matchesOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } elseif ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Site-Key, Accept');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
