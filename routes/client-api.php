<?php

use App\Http\Controllers\AlertsController;
use App\Http\Controllers\BroadcastAuthProxyController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\SyncStatusController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('api/', function () {
    return response()->json([
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    /*
    | Read layer (JSON) — consumed by fetchViaAxios.js.
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/students/{remoteId}/status', [HomeController::class, 'status'])->name('students.status');
        Route::get('/students/{remoteId}/history', [HistoryController::class, 'month'])->name('students.history');
        Route::get('/students/{remoteId}/alerts', [AlertsController::class, 'feed'])->name('students.alerts');

        Route::get('/sync/status', [SyncStatusController::class, 'show'])->name('sync.status');
        Route::post('/sync/pull', [SyncStatusController::class, 'pull'])->name('sync.pull');

        // Push device token (mobile). JSON so a failure never redirects.
        Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
        Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])->name('device-tokens.destroy');

        Route::post('/broadcasting/auth', [BroadcastAuthProxyController::class, 'authorize'])->name('broadcasting.auth');
    });

    /*
    | Write layer — Inertia requests (useForm / router), respond with back().
    */
    Route::name('app.')->group(function () {
        Route::put('/settings/notification-preferences', [PreferenceController::class, 'update'])->name('preferences.update');
        Route::post('/settings/link-request', [LinkController::class, 'store'])->name('link.request');
    });
});
