<?php

namespace App\Http\Middleware;

use App\Services\Push\DeviceTokenRegistrar;
use App\Services\Resolvers\PlatformService;
use Closure;
use Illuminate\Http\Request;
use Native\Mobile\Facades\PushNotifications;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile only, once per session: if FCM permission is ALREADY granted,
 * re-register the current device token with the server. It does NOT ask for
 * permission — the first-run priming flow (primePushNotifications) owns that.
 * New / rotated tokens arrive asynchronously via the RegisterPushToken listener.
 *
 * Guarded on `PushNotifications::checkPermission()` so it's inert until the
 * `fatlum/nativephp-push` plugin is installed.
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
            && PushNotifications::checkPermission() === 'granted'
        ) {
            PushNotifications::enroll();

            if ($token = PushNotifications::getToken()) {
                $platform = PlatformService::detect()['isIos'] ? 'ios' : 'android';
                DeviceTokenRegistrar::register($request->user(), $token, $platform);
            }

            $request->session()->put('_push_enrolled', true);
        }

        return $response;
    }
}
