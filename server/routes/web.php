<?php

use App\Livewire\AcceptInvite;
use App\Livewire\ProposalView;
use App\Mail\AppointmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Pricing cards read live rates from Admin > Settings > Rates.
    return view('welcome', ['rates' => \App\Models\Setting::rates()]);
});

Route::get('/accessibility', fn () => view('accessibility'))->name('accessibility');
Route::get('/terms', fn () => view('terms'))->name('terms');
Route::get('/privacy', fn () => view('privacy'))->name('privacy');
Route::get('/clients', fn () => view('clients'))->name('clients');
Route::get('/sitemap', fn () => view('sitemap'))->name('sitemap');

Route::post('/appointment', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'project_type' => 'required|string|max:50',
        'date' => 'required|date|after:today',
        'time' => 'required|string',
        'description' => 'nullable|string|max:2000',
    ]);

    Mail::to('jim@divstrong.com')
        ->send(new AppointmentRequest(
            name: $validated['name'],
            email: $validated['email'],
            projectType: $validated['project_type'],
            date: $validated['date'],
            time: $validated['time'],
            description: $validated['description'] ?? null,
        ));

    return response()->json(['message' => 'Appointment request sent successfully.']);
})->name('appointment.store');

Route::get('/invite/{token}', AcceptInvite::class)->name('invite.accept');

// Private, token-gated client view of their own bug reports. The token is a
// per-site secret (NOT the public embed key) emailed to the client.
Route::get('/reports/{token}', [\App\Http\Controllers\ClientBugReportController::class, 'index'])
    ->name('client.bug-reports');
Route::get('/reports/{token}/screenshot/{report}', [\App\Http\Controllers\ClientBugReportController::class, 'screenshot'])
    ->name('client.bug-reports.screenshot');

Route::get('/admin/bug-screenshots/{report}', function (\App\Models\BugReport $report) {
    abort_unless(auth()->check() && auth()->user()->hasPermission('bug_reports'), 403);
    abort_unless($report->screenshot_path, 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->response($report->screenshot_path);
})->middleware('auth')->name('bug-screenshot.show');

Route::get('/proposal/{uuid}', ProposalView::class)
    ->name('proposal.view')
    ->middleware('track.proposal.view');

Route::get('/proposal/{uuid}/pdf', [\App\Http\Controllers\ProposalPdfController::class, 'download'])
    ->name('proposal.pdf');

Route::get('/proposal/{uuid}/cover', function (string $uuid) {
    $proposal = \App\Models\Proposal::where('uuid', $uuid)->firstOrFail();
    $qr = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
        'outputType'     => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel'       => \chillerlan\QRCode\QRCode::ECC_M,
        'svgViewBoxSize' => 200,
        'addQuietzone'   => true,
        'imageBase64'    => false,
    ])))->render($proposal->public_url);
    return view('proposal-cover', ['proposal' => $proposal, 'qr' => $qr]);
})->name('proposal.cover');

Route::get('/proposal/{uuid}/badge', function (string $uuid) {
    $proposal = \App\Models\Proposal::where('uuid', $uuid)->firstOrFail();
    $qr = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
        'outputType'     => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel'       => \chillerlan\QRCode\QRCode::ECC_M,
        'svgViewBoxSize' => 200,
        'addQuietzone'   => true,
        'imageBase64'    => false,
    ])))->render($proposal->public_url);

    return view('proposal-badge', ['proposal' => $proposal, 'qr' => $qr]);
})->name('proposal.badge');

// PayPal payment endpoints
Route::prefix('proposal/{uuid}/payment')->group(function () {
    Route::post('/create-order', [\App\Http\Controllers\PayPalController::class, 'createOrder'])
        ->name('proposal.payment.create');
    Route::post('/{orderId}/capture', [\App\Http\Controllers\PayPalController::class, 'captureOrder'])
        ->name('proposal.payment.capture');
});

/*
|--------------------------------------------------------------------------
| Cold outreach opt-out
|--------------------------------------------------------------------------
|
| Public and unauthenticated by necessity — CAN-SPAM says the mechanism must not require the
| recipient to log in, create an account, or supply anything beyond their address. The route
| is signed, so the prospect id in the URL cannot be edited to opt somebody else out.
|
| GET confirms, POST performs. That split matters twice over: a link prefetched by a mail
| client or scanned by a security appliance must not silently unsubscribe someone, and RFC
| 8058 one-click unsubscribes arrive as a bare POST from Gmail or Yahoo with no page loaded.
*/
Route::get('unsubscribe/{prospect}', [\App\Http\Controllers\UnsubscribeController::class, 'show'])
    ->name('outreach.unsubscribe')
    ->middleware('signed');

Route::post('unsubscribe/{prospect}', [\App\Http\Controllers\UnsubscribeController::class, 'store'])
    ->name('outreach.unsubscribe.confirm')
    ->middleware('signed')
    // A one-click POST from Gmail or Yahoo carries no session and therefore no CSRF token.
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
