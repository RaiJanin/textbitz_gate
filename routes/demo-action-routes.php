<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/**
 * Populates the local offline demo dataset (guardian login + linked children +
 * tap history). Only available while demo mode is enabled.
 */
Route::post('/make-demo-account', function () {
    if (! config('app.demo_mode', false)) {
        return back()->with('error', 'Demo mode is disabled.');
    }

    Artisan::call('db:seed', [
        '--class' => DemoSeeder::class,
        '--force' => true,
    ]);

    return back()->with('success', true);
})->name('make-demo');
