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

            \Djurovicigoor\AppLifecycle\AppLifecycleServiceProvider::class,

            // FCM/APNs push — `fatlum/nativephp-push` (free, MIT). It backs
            // NativePHP core's `PushNotifications` facade + the
            // `PushNotification\TokenGenerated` event natively, and adds
            // `Lumi\NativePush\Events\PushNotificationReceived` for data
            // messages — which is exactly what the app code targets.
            //
            // Remaining setup:
            //   - resources/google-services.json      (Android)
            //   - resources/GoogleService-Info.plist  (iOS) + APS_ENVIRONMENT
            //   - server: FCM_ENABLED=true, FCM_PROJECT_ID, FIREBASE_CREDENTIALS
            //   - php artisan native:run android
            //
            // Wired: usePushNotifications.js / usePushPriming.js (JS via
            // #nativephp) · EnrollPushNotifications middleware · RegisterPushToken
            // + HandlePushMessage listeners (explicit in AppServiceProvider) ·
            // POST /api/device-tokens → server. useLocalNotifications.js is the
            // in-app / no-token fallback.
            \Lumi\NativePush\PushServiceProvider::class,
        ];
    }
}