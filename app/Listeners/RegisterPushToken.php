<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Push\DeviceTokenRegistrar;
use App\Services\Resolvers\PlatformService;
use Native\Mobile\Events\PushNotification\TokenGenerated;

/**
 * The NativePHP push plugin fires TokenGenerated once the OS hands over an
 * FCM/APNS token (and again whenever it rotates). Persist + forward it.
 */
class RegisterPushToken
{
    public function handle(TokenGenerated $event): void
    {
        if (! PlatformService::isRunningNatively()) {
            return;
        }

        $platform = PlatformService::detect()['isIos'] ? 'ios' : 'android';

        // The client holds a single local account.
        DeviceTokenRegistrar::register(User::query()->first(), $event->token, $platform);
    }
}
