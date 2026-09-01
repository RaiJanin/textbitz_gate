<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Data\FlushPendingSyncs;
use Djurovicigoor\AppLifecycle\Events\AppBackgrounded;
use Djurovicigoor\AppLifecycle\Events\AppForegrounded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function bridgedUser(): User
{
    return User::factory()->create([
        'phone_number' => '+639170000640',
        'remote_id' => 1,
        'remote_token' => 'tok',
    ]);
}

function pendingGuardianPref(): NotificationPreference
{
    return NotificationPreference::create([
        'role' => 'guardian',
        'arrival' => false,
        'departure' => true,
        'late_alert' => true,
        'weekly_summary' => true,
        'sync_status' => NotificationPreference::SYNC_STATUS_PENDING,
    ]);
}

beforeEach(function () {
    Cache::put('remote_connectivity', true, 60);
    Http::fake([
        '*/api/health' => Http::response(['alive' => true]),
        '*/api/user' => Http::response(['id' => 1]),
        '*/api/me' => Http::response(['guardian' => ['students' => []]]),
        '*/api/notification-preferences' => Http::response(['ok' => true]),
        '*' => Http::response([], 200),
    ]);
});

it('flushes a pending preference synchronously (no queue worker)', function () {
    bridgedUser();
    $pref = pendingGuardianPref();

    $flushed = FlushPendingSyncs::run();

    expect($flushed)->toBeGreaterThan(0)
        ->and($pref->fresh()->sync_status)->toBe(NotificationPreference::SYNC_STATUS_SYNCED);
});

it('runs the workers when the app is foregrounded', function () {
    bridgedUser();
    $pref = pendingGuardianPref();

    event(new AppForegrounded(now()->valueOf()));

    expect($pref->fresh()->sync_status)->toBe(NotificationPreference::SYNC_STATUS_SYNCED)
        ->and(Cache::get('gate.last_foreground_at'))->not->toBeNull();
    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/me'));
});

it('flushes pending writes and records the timestamp when backgrounded', function () {
    bridgedUser();
    $pref = pendingGuardianPref();

    event(new AppBackgrounded(now()->valueOf()));

    expect($pref->fresh()->sync_status)->toBe(NotificationPreference::SYNC_STATUS_SYNCED)
        ->and(Cache::get('gate.last_background_at'))->not->toBeNull();
});
