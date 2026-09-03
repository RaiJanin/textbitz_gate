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

it('drops locally cached students the account is no longer linked to', function () {
    Cache::put('remote_connectivity', true, 60);
    Http::fake([
        '*/api/me' => Http::response(['guardian' => ['students' => []]]), // linked to nobody
        '*/api/notification-preferences' => Http::response(['preferences' => []]),
        '*/api/health' => Http::response(['alive' => true]),
    ]);

    // Stale rows from a previous login / demo seed.
    \App\Models\Student::create(['remote_id' => 1, 'full_name' => 'Diana Reyes']);
    \App\Models\Student::create(['remote_id' => 2, 'full_name' => 'Marco Reyes']);

    syncUser();
    PullTapsFromServer::refreshLinkedStudents();

    expect(\App\Models\Student::count())->toBe(0);
});

it('wipes the previous account cache when a different account is adopted', function () {
    Cache::put(PullTapsFromServer::CACHE_OWNER_KEY, 99); // cache belongs to remote user 99
    \App\Models\Student::create(['remote_id' => 5, 'full_name' => 'Someone Elses Kid']);
    \App\Models\TapEvent::create([
        'student_id' => 1, 'direction' => 'in', 'tapped_at' => now(),
    ]);

    PullTapsFromServer::adoptAccount(syncUser()); // remote_id 1

    expect(\App\Models\Student::count())->toBe(0)
        ->and(\App\Models\TapEvent::count())->toBe(0)
        ->and((int) Cache::get(PullTapsFromServer::CACHE_OWNER_KEY))->toBe(1);
});

it('adopts an unknown-owner cache without wiping it', function () {
    \App\Models\Student::create(['remote_id' => 1, 'full_name' => 'Diana Reyes']);

    PullTapsFromServer::adoptAccount(syncUser()); // remote_id 1, no owner key set

    expect(\App\Models\Student::count())->toBe(1)
        ->and((int) Cache::get(PullTapsFromServer::CACHE_OWNER_KEY))->toBe(1);
});

it('pulls the switched-to account\'s students into the cache on login', function () {
    Cache::put('remote_connectivity', true, 60);
    Cache::put(PullTapsFromServer::CACHE_OWNER_KEY, 99); // cache belongs to a previous account (user 99)
    \App\Models\Student::create(['remote_id' => 500, 'full_name' => 'Old Account Kid']);

    Http::fake([
        '*/api/me' => Http::response([
            'guardian' => ['students' => [[
                'id' => 7, 'full_name' => 'Bea Cruz', 'grade' => '9',
                'school' => ['id' => 1, 'name' => 'Sampaguita', 'timezone' => 'Asia/Manila'],
            ]]],
        ]),
        '*/api/notification-preferences' => Http::response(['preferences' => []]),
        '*/api/students/7/status' => Http::response(['date' => now('Asia/Manila')->toDateString(), 'timeline' => []]),
        '*/api/health' => Http::response(['alive' => true]),
        '*/api/login' => Http::response(['user' => ['id' => 2], 'token' => 'tok-2']),
        '*' => Http::response([], 200),
    ]);

    // User 2 (remote_id 2) logs in on a device whose cache still holds user 99's data.
    User::factory()->create([
        'phone_number' => '+639170000222',
        'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
        'remote_id' => 2,
        'remote_token' => 'tok-2',
    ]);

    $this->post('/login', ['phone_number' => '+639170000222', 'password' => 'secret123'])
        ->assertRedirect(route('app.dashboard', absolute: false));

    expect(\App\Models\Student::where('remote_id', 500)->exists())->toBeFalse() // old account's kid gone
        ->and(\App\Models\Student::where('remote_id', 7)->value('full_name'))->toBe('Bea Cruz') // new account's kid present
        ->and((int) Cache::get(PullTapsFromServer::CACHE_OWNER_KEY))->toBe(2);
});
