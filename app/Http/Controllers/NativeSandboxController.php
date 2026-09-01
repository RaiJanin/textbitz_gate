<?php

namespace App\Http\Controllers;

use App\Services\Resolvers\PlatformService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Native\Mobile\Facades\Device;
use Native\Mobile\Facades\System;

/**
 * Dev-only "does the native bridge work here?" sandbox. Registered from
 * routes/debug-routes.php only when APP_DEBUG is true.
 */
class NativeSandboxController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Debug/NativeSandbox', [
            'platform' => PlatformService::detect(),
            'nativeLoaded' => PlatformService::isRunningNatively(),
            'hasBridgeFn' => function_exists('nativephp_call'),
        ]);
    }

    /**
     * Runs every PHP-side native facade call this app relies on, each isolated
     * in its own try/catch, and reports what came back.
     */
    public function probe(): JsonResponse
    {
        $checks = [
            $this->check('extension_loaded(nativephp)', fn () => extension_loaded('nativephp')),
            $this->check('function_exists(nativephp_call)', fn () => function_exists('nativephp_call')),
            $this->check('PlatformService::detect()', fn () => PlatformService::detect()),
        ];

        // The bridge calls block for ~2s each without the native runtime, so
        // only run them where they can actually resolve.
        if (PlatformService::isRunningNatively()) {
            $checks = array_merge($checks, [
                $this->check('System::isAndroid()', fn () => System::isAndroid()),
                $this->check('System::isIos()', fn () => System::isIos()),
                $this->check('Device::getInfo()', fn () => Device::getInfo()),
                $this->check('Device::getId()', fn () => Device::getId()),
                $this->check('Device::getBatteryInfo()', fn () => Device::getBatteryInfo()),
            ]);
        }

        return response()->json([
            'nativeRuntime' => PlatformService::isRunningNatively(),
            'note' => PlatformService::isRunningNatively()
                ? null
                : 'No native runtime here — bridge facade calls skipped. Run this from `php artisan native:run android`.',
            'checks' => $checks,
        ]);
    }

    /**
     * @return array{name:string, ok:bool, value:mixed, error:?string, ms:float}
     */
    private function check(string $name, callable $fn): array
    {
        $start = microtime(true);

        try {
            $value = $fn();

            return [
                'name' => $name,
                'ok' => true,
                'value' => $value,
                'error' => null,
                'ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        } catch (\Throwable $e) {
            return [
                'name' => $name,
                'ok' => false,
                'value' => null,
                'error' => class_basename($e).': '.$e->getMessage(),
                'ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }
    }
}
