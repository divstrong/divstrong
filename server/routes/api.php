<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\RfpScreenController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/rfp-screens', [RfpScreenController::class, 'index']);
    Route::post('/rfp-screens', [RfpScreenController::class, 'store']);
    Route::get('/rfp-screens/{rfpScreen}', [RfpScreenController::class, 'show']);
    Route::post('/rfp-screens/{rfpScreen}/rescan', [RfpScreenController::class, 'rescan']);
    Route::post('/rfp-screens/{rfpScreen}/create-proposal', [RfpScreenController::class, 'createProposal']);
    Route::delete('/rfp-screens/{rfpScreen}', [RfpScreenController::class, 'destroy']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    Route::get('/proposals', [ProposalController::class, 'index']);
    Route::post('/proposals', [ProposalController::class, 'store']);
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show']);
    Route::put('/proposals/{proposal}', [ProposalController::class, 'update']);
    Route::post('/proposals/{proposal}/send', [ProposalController::class, 'send']);
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy']);

    Route::post('/proposals/{proposal}/scope-items', [ProposalController::class, 'addScopeItem']);
    Route::put('/proposals/{proposal}/scope-items/{itemId}', [ProposalController::class, 'updateScopeItem']);
    Route::delete('/proposals/{proposal}/scope-items/{itemId}', [ProposalController::class, 'deleteScopeItem']);

    Route::post('/proposals/{proposal}/cost-items', [ProposalController::class, 'addCostItem']);
    Route::put('/proposals/{proposal}/cost-items/{itemId}', [ProposalController::class, 'updateCostItem']);
    Route::delete('/proposals/{proposal}/cost-items/{itemId}', [ProposalController::class, 'deleteCostItem']);

    Route::post('/proposals/{proposal}/milestones', [ProposalController::class, 'addMilestone']);
    Route::put('/proposals/{proposal}/milestones/{itemId}', [ProposalController::class, 'updateMilestone']);
    Route::delete('/proposals/{proposal}/milestones/{itemId}', [ProposalController::class, 'deleteMilestone']);
});
