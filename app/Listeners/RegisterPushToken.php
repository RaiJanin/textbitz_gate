<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Push\DeviceTokenRegistrar;
use App\Services\Resolvers\PlatformService;
use Native\Mobile\Events\PushNotification\TokenGenerated;

/**
 * `fatlum/nativephp-push` fires core's `PushNotification\TokenGenerated` once
 * the OS hands over an FCM/APNs token (via `PushNotification.RequestPermission`),
 * and again whenever it rotates. Persist it and forward it to the server.
 * Registered explicitly in AppServiceProvider.
 */
class RegisterPushToken
{
    public function handle(TokenGenerated $event): void
    {
        if (! PlatformService::isRunningNatively()) {
            return;
        }

        $platform = PlatformService::detect()['isIos'] ? 'ios' : 'android';

        DeviceTokenRegistrar::register(User::query()->first(), $event->token, $platform);
    }
}
