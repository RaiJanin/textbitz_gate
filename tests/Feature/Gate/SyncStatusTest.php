<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Data\PullTapsFromServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function syncUser(): User
{
    return User::factory()->create([
        'phone_number' => '+639170000123',
        'remote_id' => 1,
        'remote_token' => 'tok',
    ]);
}

it('reports the last sync + pending write count as plain JSON', function () {
    Cache::put(PullTapsFromServer::REPORT_CACHE_KEY, [
        'at' => now()->toIso8601String(),
        'new_taps' => 3,
        'updated_taps' => 1,
        'students' => ['Diana Reyes'],
    ]);

    NotificationPreference::create([
        'role' => 'guardian',
        'arrival' => false,
        'departure' => true,
        'late_alert' => true,
        'weekly_summary' => true,
        'sync_status' => NotificationPreference::SYNC_STATUS_PENDING,
    ]);

    $this->actingAs(syncUser())
        ->getJson('/api/sync/status')
        ->assertOk()
        ->assertJsonPath('report.new_taps', 3)
        ->assertJsonPath('pending_writes', 1)
        ->assertJsonStructure(['online', 'report' => ['at', 'new_taps', 'updated_taps', 'students'], 'pending_writes']);
});

it('never redirects an unauthenticated api call to login', function () {
    $this->getJson('/api/sync/status')->assertUnauthorized(); // 401 JSON, not a 302
});

it('the recurring pull writes a sync report with change counts', function () {
    Http::fake([
        '*/api/me' => Http::response([
            'guardian' => ['students' => [[
                'id' => 1, 'full_name' => 'Diana Reyes', 'grade' => '9', 'section' => 'Rizal',
                'school' => ['id' => 1, 'name' => 'Sampaguita', 'timezone' => 'Asia/Manila'],
            ]]],
        ]),
        '*/api/notification-preferences' => Http::response(['preferences' => []]),
        '*/api/students/1/status' => Http::response([
            'date' => now('Asia/Manila')->toDateString(),
            'timeline' => [
                ['direction' => 'in', 'at' => '07:30', 'gate' => 'Main Gate', 'is_late' => false],
            ],
        ]),
        '*/api/health' => Http::response(['alive' => true]),
    ]);

    Cache::put('remote_connectivity', true, 60);

    syncUser();
    PullTapsFromServer::refreshLinkedStudents();

    $report = Cache::get(PullTapsFromServer::REPORT_CACHE_KEY);

    expect($report)->not->toBeNull()
        ->and($report['new_taps'])->toBe(1)
        ->and($report['students'])->toContain('Diana Reyes');
});
