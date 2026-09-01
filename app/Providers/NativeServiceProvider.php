<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     * This is a security measure to prevent transitive dependencies from
     * automatically registering plugins without your explicit consent.
     *
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            \Native\Mobile\Providers\DialogServiceProvider::class,
            \Native\Mobile\Providers\DeviceServiceProvider::class,

            // Background push (FCM/APNS). Install `nativephp/mobile-firebase`,
            // drop google-services.json / GoogleService-Info.plist in the app
            // root, then register its provider here. The rest is already wired:
            //   - PHP: EnrollPushNotifications middleware + RegisterPushToken listener
            //   - JS:  startPushNotifications() in app.js (mobile-only)
            //   → device token is POSTed to /api/device-tokens and forwarded to the server.
            // \Native\Mobile\Firebase\FirebaseServiceProvider::class,
        ];
    }
}