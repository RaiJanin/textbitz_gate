<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing/Main', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('auth-screen');

require __DIR__.'/auth.php';
require __DIR__.'/inertia-pages.php';
require __DIR__.'/client-api.php';
require __DIR__.'/demo-action-routes.php';
require __DIR__.'/debug-routes.php';
