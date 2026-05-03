<?php

namespace App\Providers;

use App\Models\Application;
use App\Policies\ApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Services\GeminiService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeminiService::class, function () {
            return new GeminiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);

        // Stricter rate limiting for authentication
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(3) // 3 attempts per minute
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many authentication attempts. Please try again in 1 minute.',
                    ], 429);
                });
        });

        // Rate limiting for AI endpoints
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(2) // 2 requests per minute per user
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Rate limit exceeded. Please try again later.',
                    ], 429);
                });
        });

        // Rate limiting for application CRUD operations
        RateLimiter::for('app', function (Request $request) {
            return Limit::perMinute(30) // 30 operations per minute per user
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Rate limit exceeded. Please try again later.',
                    ], 429);
                });
        });
    }
}
