<?php

use App\Models\User;
use App\Services\Debug\WorkerActivityLog;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    WorkerActivityLog::register();
    $this->actingAs(User::factory()->create(['phone_number' => '+639170000700']));
});

it('exposes scheduler + queue status as JSON', function () {
    $this->getJson('/debug/workers')
        ->assertOk()
        ->assertJsonStructure([
            'scheduler' => ['tasks', 'recent'],
            'queue' => ['pending', 'failed', 'processed_ok', 'processed_fail', 'recent'],
            'connectivity' => ['online', 'checked_recently'],
        ]);
});

it('folds recorded scheduler runs into a per-task summary', function () {
    Cache::put(WorkerActivityLog::KEY, [
        'scheduler' => [
            ['task' => 'refresh-linked-students', 'runtime_ms' => 420, 'ok' => true, 'at' => now()->toIso8601String()],
            ['task' => 'refresh-linked-students', 'runtime_ms' => 900, 'ok' => true, 'at' => now()->subMinute()->toIso8601String()],
            ['task' => 'server-connectivity-heartbeat', 'runtime_ms' => 30, 'ok' => true, 'at' => now()->toIso8601String()],
        ],
        'queue' => [],
        'counters' => ['queue_ok' => 3, 'queue_fail' => 1],
    ]);

    $snapshot = WorkerActivityLog::snapshot();

    expect($snapshot['scheduler']['tasks'])->toHaveCount(2)           // one row per task
        ->and($snapshot['scheduler']['tasks'][0]['runtime_ms'])->toBe(420) // most recent kept
        ->and($snapshot['queue']['processed_ok'])->toBe(3)
        ->and($snapshot['queue']['processed_fail'])->toBe(1);
});

it('clears the activity log', function () {
    Cache::put(WorkerActivityLog::KEY, ['scheduler' => [['task' => 'x']], 'queue' => [], 'counters' => []]);

    $this->postJson('/debug/workers/clear')->assertOk();

    expect(Cache::get(WorkerActivityLog::KEY))->toBeNull();
});
