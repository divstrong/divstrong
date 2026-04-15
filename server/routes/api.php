<?php

use App\Http\Controllers\Api\AuthController;
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
    Route::delete('/rfp-screens/{rfpScreen}', [RfpScreenController::class, 'destroy']);
});
