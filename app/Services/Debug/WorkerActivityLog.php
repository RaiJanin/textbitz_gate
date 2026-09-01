<?php

namespace App\Services\Debug;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

/**
 * Records scheduler + queue-worker activity into the cache so the on-device
 * WorkerDebugOverlay can show what the background processes are doing. Only
 * wired when APP_DEBUG is on.
 */
class WorkerActivityLog
{
    public const KEY = 'debug.worker_activity';

    private const KEEP = 25;

    public static function register(): void
    {
        Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $e) {
            self::push('scheduler', [
                'task' => self::taskName($e),
                'runtime_ms' => (int) round(($e->runtime ?? 0) * 1000),
                'ok' => true,
            ]);
        });

        Event::listen(ScheduledTaskSkipped::class, function (ScheduledTaskSkipped $e) {
            self::push('scheduler', ['task' => self::taskName($e), 'ok' => true, 'skipped' => true]);
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $e) {
            self::push('scheduler', [
                'task' => self::taskName($e),
                'ok' => false,
                'error' => mb_substr((string) ($e->exception?->getMessage() ?? 'failed'), 0, 200),
            ]);
        });

        Event::listen(JobProcessing::class, function (JobProcessing $e) {
            self::bump('queue_started');
        });

        Event::listen(JobProcessed::class, function (JobProcessed $e) {
            self::push('queue', ['job' => class_basename($e->job->resolveName()), 'ok' => true]);
        });

        Event::listen(JobFailed::class, function (JobFailed $e) {
            self::push('queue', [
                'job' => class_basename($e->job->resolveName()),
                'ok' => false,
                'error' => mb_substr($e->exception->getMessage(), 0, 200),
            ]);
        });
    }

    public static function snapshot(): array
    {
        $log = Cache::get(self::KEY, ['scheduler' => [], 'queue' => [], 'counters' => []]);

        $lastByTask = [];
        foreach ($log['scheduler'] as $row) {
            $lastByTask[$row['task']] ??= $row;
        }

        return [
            'scheduler' => [
                'tasks' => array_values($lastByTask),
                'recent' => $log['scheduler'],
            ],
            'queue' => [
                'pending' => self::tableCount('jobs'),
                'failed' => self::tableCount('failed_jobs'),
                'oldest_pending_at' => self::oldestPending(),
                'processed_ok' => $log['counters']['queue_ok'] ?? 0,
                'processed_fail' => $log['counters']['queue_fail'] ?? 0,
                'recent' => $log['queue'],
            ],
            'connectivity' => [
                'online' => (bool) Cache::get('remote_connectivity_last_known', false),
                'checked_recently' => Cache::has('remote_connectivity'),
            ],
            'sync' => Cache::get(\App\Services\Data\PullTapsFromServer::REPORT_CACHE_KEY),
        ];
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }

    private static function push(string $stream, array $entry): void
    {
        $log = Cache::get(self::KEY, ['scheduler' => [], 'queue' => [], 'counters' => []]);

        $entry['at'] = now()->toIso8601String();
        array_unshift($log[$stream], $entry);
        $log[$stream] = array_slice($log[$stream], 0, self::KEEP);

        $counter = $stream.($entry['ok'] ? '_ok' : '_fail');
        $log['counters'][$counter] = ($log['counters'][$counter] ?? 0) + 1;

        Cache::put(self::KEY, $log, now()->addDay());
    }

    private static function bump(string $counter): void
    {
        $log = Cache::get(self::KEY, ['scheduler' => [], 'queue' => [], 'counters' => []]);
        $log['counters'][$counter] = ($log['counters'][$counter] ?? 0) + 1;
        Cache::put(self::KEY, $log, now()->addDay());
    }

    private static function taskName(object $event): string
    {
        $task = $event->task ?? null;

        return $task?->name
            ?? (method_exists($task, 'getSummaryForDisplay') ? $task->getSummaryForDisplay() : 'task');
    }

    private static function tableCount(string $table): ?int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : null;
    }

    private static function oldestPending(): ?string
    {
        if (! Schema::hasTable('jobs')) {
            return null;
        }

        $ts = DB::table('jobs')->min('created_at');

        return $ts ? now()->createFromTimestamp($ts)->toIso8601String() : null;
    }
}
