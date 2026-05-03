<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\InterviewRoundController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;


// Authentication routes with stricter rate limiting
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
});

// Public / Semi-public routes
Route::get('/me', [LoginController::class, 'me']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/refresh', [LoginController::class, 'refresh']);

    // Application CRUD with rate limiting
    Route::middleware('throttle:app')->group(function () {
        Route::apiResource('applications', ApplicationController::class);
        Route::apiResource('applications.interview-rounds', InterviewRoundController::class);
    });

    // Dashboard statistics
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // AI endpoints with stricter rate limiting
    Route::prefix('ai')->middleware('throttle:ai')->group(function () {
        Route::post('/chat',         [AiController::class, 'chat']);
        Route::post('/cover-letter', [AiController::class, 'coverLetter']);
        Route::post('/insights',     [AiController::class, 'insights']);
        Route::post('/tag-job',      [AiController::class, 'tagJobDescription']);
    });
});

