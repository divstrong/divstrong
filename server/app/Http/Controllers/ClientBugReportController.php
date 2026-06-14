<?php

namespace App\Http\Controllers;

use App\Models\BugReport;
use App\Models\BugReporterSite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ClientBugReportController extends Controller
{
    /**
     * Read-only list of a site's bug reports, gated solely by the private
     * read token in the URL. This token is NOT the public embed key — it is
     * never exposed in the client's page source.
     */
    public function index(string $token): View
    {
        $site = $this->resolveSite($token);

        $reports = $site->bugReports()
            ->latest()
            ->get();

        return view('bug-reports.client', [
            'site' => $site,
            'reports' => $reports,
        ]);
    }

    public function screenshot(string $token, BugReport $report): Response
    {
        $site = $this->resolveSite($token);

        // The report must belong to the token's site, or it is a 404 — a token
        // for site A can never read site B's screenshots.
        abort_unless($report->bug_reporter_site_id === $site->id, 404);
        abort_unless($report->screenshot_path, 404);

        return Storage::disk('local')->response($report->screenshot_path);
    }

    private function resolveSite(string $token): BugReporterSite
    {
        // Reject obviously malformed tokens before touching the DB.
        abort_if(strlen($token) < 20, 404);

        return BugReporterSite::where('read_token', $token)->firstOrFail();
    }
}
