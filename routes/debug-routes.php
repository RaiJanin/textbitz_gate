<?php

use App\Http\Controllers\NativeSandboxController;
use App\Http\Controllers\WorkerStatusController;
use App\Services\Data\PullTapsFromServer;
use App\Services\Resolvers\PlatformService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\System;

if (config('app.debug')) {
    Route::get('/debug/auth-redirects', function () {
        $path = storage_path('logs/auth-redirect.log');

        if (! File::exists($path)) {
            return response()->json(['entries' => [], 'note' => 'No auth redirects recorded — good sign.']);
        }

        $entries = collect(File::lines($path))
            ->filter()
            ->map(fn ($line) => json_decode($line, true) ?? $line)
            ->reverse()
            ->take(50)
            ->values();

        return response()->json(['count' => $entries->count(), 'entries' => $entries]);
    });

    Route::get('/debug-extensions', function () {
        return response()->json([
            'loadedExtensions' => get_loaded_extensions(),
            'nativeIsLoaded' => PlatformService::isRunningNatively(),
            'platformServiceResponse' => PlatformService::detect(),
            'nativeResponse' => [
                'android' => System::isAndroid(),
                'ios' => System::isIos(),
            ],
            'deviceInfo' => Device::getInfo(),
        ]);
    });

    Route::get('/debug-native-extensions', function () {
        return response()->json([
            'nativeIsLoaded' => PlatformService::isRunningNatively(),
        ]);
    });

    Route::get('/debug/logs', function () {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return response('No logs found.', 404);
        }

        $lines = collect(explode("\n", File::get($path)))
            ->reverse()
            ->take(200)
            ->reverse()
            ->implode("\n");

        return response()->json($lines);
    });

    Route::get('/debug/demo', function () {
        $counts = fn () => [
            'users' => \App\Models\User::count(),
            'students' => \App\Models\Student::count(),
            'gates' => \App\Models\Gate::count(),
            'tap_events' => \App\Models\TapEvent::count(),
            'preferences' => \App\Models\NotificationPreference::count(),
        ];

        $before = $counts();

        if (request()->boolean('seed')) {
            (new \Database\Seeders\DemoSeeder)->run(connectRemote: request()->boolean('connect'));
        }

        return response()->json([
            'demo_mode' => config('app.demo_mode'),
            'before' => $before,
            'after' => $counts(),
            'hint' => 'Add ?seed=1 to force-seed (idempotent), &connect=1 to also bridge the remote login.',
        ]);
    });

    Route::get('/debug/taps/refresh', function () {
        PullTapsFromServer::refreshLinkedStudents();

        return response()->json(['refreshed' => true]);
    });

    // Native bridge sandbox
    Route::get('/native-sandbox', [NativeSandboxController::class, 'index'])->name('native-sandbox');
    Route::get('/debug/native/probe', [NativeSandboxController::class, 'probe']);

    // Scheduler + queue-worker overlay
    Route::get('/debug/workers', [WorkerStatusController::class, 'show']);
    Route::post('/debug/workers/run-scheduler', [WorkerStatusController::class, 'runScheduler']);
    Route::post('/debug/workers/process-queue', [WorkerStatusController::class, 'processQueue']);
    Route::post('/debug/workers/retry-failed', [WorkerStatusController::class, 'retryFailed']);
    Route::post('/debug/workers/clear', [WorkerStatusController::class, 'clear']);
}
