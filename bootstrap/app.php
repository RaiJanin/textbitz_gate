<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'push.enroll' => \App\Http\Middleware\EnrollPushNotifications::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->header('X-Inertia') ? false : $request->is('api/*'),
        );

        // TEMP DIAGNOSTIC: record exactly which request bounces the app to login.
        // Read it at GET /debug/auth-redirects (debug mode only).
        $exceptions->report(function (AuthenticationException $e) {
            $request = request();

            $entry = [
                'at' => now()->toDateTimeString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'inertia' => $request->header('X-Inertia') ? 'yes' : 'no',
                'requested_with' => $request->header('X-Requested-With'),
                'referer' => $request->header('referer'),
                'has_session_cookie' => $request->hasCookie(config('session.cookie')),
                'has_remember_cookie' => collect($request->cookies->keys())->contains(fn ($k) => str_starts_with($k, 'remember_')),
            ];

            Log::warning('AUTH_REDIRECT', $entry);

            try {
                \Illuminate\Support\Facades\File::append(
                    storage_path('logs/auth-redirect.log'),
                    json_encode($entry, JSON_UNESCAPED_SLASHES).PHP_EOL,
                );
            } catch (\Throwable) {
                // best effort
            }
        });
    })->create();
