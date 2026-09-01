<?php

namespace App\Http\Middleware;

use App\Services\Push\DeviceTokenRegistrar;
use App\Services\Resolvers\PlatformService;
use Closure;
use Illuminate\Http\Request;
use Native\Mobile\Facades\PushNotifications;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile only: request push permission and enroll for FCM/APNS once per
 * session. Token delivery is handled asynchronously by RegisterPushToken;
 * an already-issued token is picked up here synchronously.
 */
class EnrollPushNotifications
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->user()
            && ! $request->session()->get('_push_enrolled')
            && PlatformService::isRunningNatively()
        ) {
            $status = PushNotifications::checkPermission();

            if ($status !== 'denied') {
                PushNotifications::enroll();

                if ($token = PushNotifications::getToken()) {
                    $platform = PlatformService::detect()['isIos'] ? 'ios' : 'android';
                    DeviceTokenRegistrar::register($request->user(), $token, $platform);
                }
            }

            $request->session()->put('_push_enrolled', true);
        }

        return $response;
    }
}
