<?php

namespace App\Support;

use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Facades\Log;

/**
 * The single source of truth for the client's periodic background work.
 *
 * Desktop/dev: `routes/console.php` schedules each of these every minute.
 * Device: NativePHP has no scheduler, so the app-lifecycle plugin runs the
 * exact same set on every foreground (RunScheduledWorkers). Add a task here
 * once and both paths pick it up.
 */
class Workers
{
    /**
     * @return array<string, callable(): void>  name => task
     */
    public static function tasks(): array
    {
        return [
            'server-connectivity-heartbeat' => fn () => ServerConnectivityService::checkAndNotify(),
            'refresh-linked-students' => fn () => PullTapsFromServer::refreshLinkedStudents(),
            'refresh-gates' => fn () => PullTapsFromServer::refreshGates(),
        ];
    }

    /**
     * Run every task now, each isolated so one failure doesn't stop the rest.
     */
    public static function runAll(): void
    {
        foreach (self::tasks() as $name => $task) {
            try {
                $task();
            } catch (\Throwable $e) {
                Log::warning("Worker [{$name}] failed", ['error' => $e->getMessage()]);
            }
        }
    }
}
