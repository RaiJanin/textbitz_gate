<?php

namespace App\Http\Controllers;

use App\Services\Debug\WorkerActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Dev-only surface for the on-device scheduler / queue overlay. Registered from
 * routes/debug-routes.php only when APP_DEBUG is true.
 */
class WorkerStatusController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(WorkerActivityLog::snapshot());
    }

    public function runScheduler(): JsonResponse
    {
        Artisan::call('schedule:run');

        return response()->json([
            'output' => trim(Artisan::output()),
            'snapshot' => WorkerActivityLog::snapshot(),
        ]);
    }

    public function processQueue(): JsonResponse
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 15,
            '--tries' => 1,
        ]);

        return response()->json([
            'output' => trim(Artisan::output()),
            'snapshot' => WorkerActivityLog::snapshot(),
        ]);
    }

    public function retryFailed(): JsonResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return response()->json(['output' => trim(Artisan::output())]);
    }

    public function clear(): JsonResponse
    {
        WorkerActivityLog::clear();

        return response()->json(['cleared' => true]);
    }
}
