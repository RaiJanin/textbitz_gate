<?php

use App\Models\LinkRequest;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function gateUser(): User
{
    return User::factory()->create([
        'remote_id' => 42,
        'remote_token' => 'plain-remote-token',
        'remote_synced_at' => now(),
    ]);
}

it('proxies student status from the server', function () {
    Http::fake([
        '*/api/health' => Http::response(['alive' => true], 200),
        '*/api/students/7/status' => Http::response([
            'date' => '2026-08-28',
            'presence' => 'at_school',
            'first_in' => '07:30',
            'timeline' => [],
        ], 200),
        '*/api/me' => Http::response(['guardian' => null, 'student' => null], 200),
        '*/api/notification-preferences' => Http::response(['preferences' => []], 200),
    ]);

    $this->actingAs(gateUser())
        ->getJson('/api/students/7/status')
        ->assertOk()
        ->assertJsonPath('presence', 'at_school');
});

it('serves a stale local status when the server is unreachable', function () {
    Http::fake([
        '*/api/students/*/status' => Http::response('', 500),
        '*' => Http::response('', 500),
    ]);

    $student = Student::create([
        'remote_id' => 7,
        'full_name' => 'Diana Reyes',
        'school_timezone' => 'Asia/Manila',
        'synced_at' => now(),
    ]);
    $student->tapEvents()->create([
        'direction' => 'in',
        'tapped_at' => now(),
        'is_late' => false,
        'gate_name' => 'Main Gate',
        'synced_at' => now(),
    ]);

    $this->actingAs(gateUser())
        ->getJson('/api/students/7/status')
        ->assertOk()
        ->assertJsonPath('stale', true)
        ->assertJsonPath('presence', 'at_school');
});

it('updates a preference locally and marks it pending when offline', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $this->actingAs(gateUser())
        ->from('/settings')
        ->put('/settings/notification-preferences', ['role' => 'guardian', 'arrival' => false])
        ->assertRedirect('/settings');

    $pref = NotificationPreference::where('role', 'guardian')->firstOrFail();
    expect($pref->arrival)->toBeFalse()
        ->and($pref->sync_status)->toBe(NotificationPreference::SYNC_STATUS_PENDING);
});

it('queues a link request', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $this->actingAs(gateUser())
        ->from('/settings')
        ->post('/settings/link-request', ['code' => 'gate-sofia'])
        ->assertRedirect('/settings');

    expect(LinkRequest::where('code', 'GATE-SOFIA')->where('sync_status', 'pending')->exists())->toBeTrue();
});

it('flushes pending preference changes on server reconnect', function () {
    Http::fake([
        '*/api/health' => Http::response(['alive' => true], 200),
        '*/api/notification-preferences' => Http::response(['preference' => []], 200),
        '*/api/me' => Http::response(['guardian' => null, 'student' => null], 200),
    ]);

    gateUser();
    $pref = NotificationPreference::create([
        'role' => 'guardian',
        'arrival' => false,
        'sync_status' => NotificationPreference::SYNC_STATUS_PENDING,
    ]);

    \App\Services\Data\DataSyncToJob::retryPendingSyncs();

    expect($pref->fresh()->sync_status)->toBe(NotificationPreference::SYNC_STATUS_SYNCED);
});
