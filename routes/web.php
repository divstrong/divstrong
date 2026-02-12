<?php

use App\Livewire\ProposalView;
use App\Mail\AppointmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

Route::get('/proposal/{uuid}', ProposalView::class)
    ->name('proposal.view')
    ->middleware('track.proposal.view');
