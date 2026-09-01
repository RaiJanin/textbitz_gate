<?php

namespace App\Services\Resolvers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Enums\InertiaControllerNames;

class RouteMacroService
{
    private static function page(): void
    {
        Route::macro('page', function ($uri, $page, $props = []) {
            return Route::get($uri, fn () => Inertia::render("{$page}/Main", $props));
        });
    }

    private static function action(): void
    {
        Route::macro('action', function ($uri, $controller) {
            return Route::get($uri, [InertiaControllerNames::tryFrom($controller)->controller(), 'index']);
        });
    }

    public static function register(): void
    {
        self::action();
        self::page();
    }
}
