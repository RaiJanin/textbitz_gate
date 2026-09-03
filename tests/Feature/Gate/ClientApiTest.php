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

it('queues a link request tagged with the submitting account', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $user = gateUser();

    $this->actingAs($user)
        ->from('/settings')
        ->post('/settings/link-request', ['code' => 'gate-sofia'])
        ->assertRedirect('/settings');

    $linkRequest = LinkRequest::where('code', 'GATE-SOFIA')->first();

    expect($linkRequest->sync_status)->toBe('pending')
        ->and($linkRequest->user_id)->toBe($user->id); // so the push authenticates as this account
});

it('pushes the link request as the account that submitted it — not an arbitrary user', function () {
    \Illuminate\Support\Facades\Cache::put('remote_connectivity', true, 60);
    Http::fake([
        '*/api/health' => Http::response(['alive' => true]),
        '*/api/link/request' => Http::response(['linked' => true], 201),
        '*/api/me' => Http::response(['guardian' => ['students' => []]]),
        '*' => Http::response([], 200),
    ]);

    // A stale first-in-line account (would have been picked by the old code)…
    User::factory()->create(['remote_id' => 1, 'remote_token' => 'stale-tok', 'remote_synced_at' => now()->subDay()]);
    // …and the account that actually submits the code.
    $submitter = User::factory()->create(['remote_id' => 99, 'remote_token' => 'submitter-tok', 'remote_synced_at' => now()]);

    $linkRequest = LinkRequest::create([
        'user_id' => $submitter->id,
        'code' => 'GATE-XYZ',
        'sync_status' => 'pending',
    ]);

    (new \App\Jobs\PushLinkRequestJob($linkRequest))->handle();

    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/link/request')
        && $r->hasHeader('Authorization', 'Bearer submitter-tok'));

    expect($linkRequest->fresh()->sync_status)->toBe('synced');
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
