<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'push.enroll'])->name('app.')->group(function () {
    Route::action('/home', 'Home')->name('home');
    Route::action('/history', 'History')->name('history');
    Route::action('/alerts', 'Alerts')->name('alerts');
    Route::action('/settings', 'Settings')->name('settings');
    Route::action('/profile', 'Profile')->name('profile.edit');

    // Backwards-compatible landing target after login.
    Route::redirect('/dashboard', '/home')->name('dashboard');
});
