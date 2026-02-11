<?php

use App\Livewire\ProposalView;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/proposal/{uuid}', ProposalView::class)
    ->name('proposal.view')
    ->middleware('track.proposal.view');
