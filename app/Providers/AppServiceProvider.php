<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Services\Resolvers\RouteMacroService;
use App\Services\Data\DefaultDataSeeder;
use App\Support\Lifecycle\FlushOnBackground;
use App\Support\Lifecycle\RunScheduledWorkers;
use Djurovicigoor\AppLifecycle\Events\AppBackgrounded;
use Djurovicigoor\AppLifecycle\Events\AppForegrounded;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RouteMacroService::register();

        DefaultDataSeeder::dataSeed(seedDemoData: config('app.demo_mode', false));

        // Native plugin events → explicit registration (not listener
        // auto-discovery) so they survive event caching in the NativePHP build.
        Event::listen(AppForegrounded::class, [RunScheduledWorkers::class, 'handle']);
        Event::listen(AppBackgrounded::class, [FlushOnBackground::class, 'handle']);
        Event::listen(
            \Native\Mobile\Events\PushNotification\TokenGenerated::class,
            [\App\Listeners\RegisterPushToken::class, 'handle'],
        );
        Event::listen(
            \Lumi\NativePush\Events\PushNotificationReceived::class,
            [\App\Listeners\HandlePushMessage::class, 'handle'],
        );

        if (config('app.debug')) {
            \App\Services\Debug\WorkerActivityLog::register();
        }

        RateLimiter::for('recipient-pushes', function () {
            return Limit::perMinute(100);
        });
    }
}
