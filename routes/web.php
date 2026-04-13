<?php

use App\Livewire\AcceptInvite;
use App\Livewire\ProposalView;
use App\Mail\AppointmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
        'date' => 'required|date|after:today',
        'time' => 'required|string',
        'description' => 'nullable|string|max:2000',
    ]);

    Mail::to('jim@divstrong.com')
        ->send(new AppointmentRequest(
            name: $validated['name'],
            email: $validated['email'],
            date: $validated['date'],
            time: $validated['time'],
            description: $validated['description'] ?? null,
        ));

    return response()->json(['message' => 'Appointment request sent successfully.']);
})->name('appointment.store');

Route::get('/invite/{token}', AcceptInvite::class)->name('invite.accept');

Route::get('/proposal/{uuid}', ProposalView::class)
    ->name('proposal.view')
    ->middleware('track.proposal.view');

Route::get('/proposal/{uuid}/pdf', [\App\Http\Controllers\ProposalPdfController::class, 'download'])
    ->name('proposal.pdf');

// PayPal payment endpoints
Route::prefix('proposal/{uuid}/payment')->group(function () {
    Route::post('/create-order', [\App\Http\Controllers\PayPalController::class, 'createOrder'])
        ->name('proposal.payment.create');
    Route::post('/{orderId}/capture', [\App\Http\Controllers\PayPalController::class, 'captureOrder'])
        ->name('proposal.payment.capture');
});
