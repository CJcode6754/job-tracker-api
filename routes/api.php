<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\InterviewRoundController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;


Route::post('/register', RegisterController::class);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

    Route::apiResource('applications', ApplicationController::class);
    Route::apiResource('applications.interview-rounds', InterviewRoundController::class);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::prefix('ai')->middleware('throttle:ai')->group(function () {
        Route::post('/chat',         [AiController::class, 'chat']);
        Route::post('/cover-letter', [AiController::class, 'coverLetter']);
        Route::post('/insights',     [AiController::class, 'insights']);
        Route::post('/tag-job',      [AiController::class, 'tagJobDescription']);
    });
});

