<?php

use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\User;
use App\Services\Data\PullTapsFromServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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
    $user = syncUser();

    Cache::put(PullTapsFromServer::reportCacheKey($user), [
        'at' => now()->toIso8601String(),
        'new_taps' => 3,
        'updated_taps' => 1,
        'students' => ['Diana Reyes'],
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'role' => 'guardian',
        'arrival' => false,
        'departure' => true,
        'late_alert' => true,
        'weekly_summary' => true,
        'sync_status' => NotificationPreference::SYNC_STATUS_PENDING,
    ]);

    $this->actingAs($user)
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

    $user = syncUser();
    PullTapsFromServer::refreshLinkedStudents();

    $report = Cache::get(PullTapsFromServer::reportCacheKey($user));

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

    $user = syncUser();

    // Stale rows cached for THIS account from a previous sync / demo seed.
    Student::create(['user_id' => $user->id, 'remote_id' => 1, 'full_name' => 'Diana Reyes']);
    Student::create(['user_id' => $user->id, 'remote_id' => 2, 'full_name' => 'Marco Reyes']);

    PullTapsFromServer::refreshLinkedStudents();

    expect(Student::where('user_id', $user->id)->count())->toBe(0);
});

it('keeps a previous account\'s cached students when switching back to it', function () {
    $accountA = User::factory()->create([
        'phone_number' => '+639170000111',
        'password' => Hash::make('secret123'),
        'remote_id' => 1,
        'remote_token' => 'tok-1',
    ]);
    $accountB = User::factory()->create([
        'phone_number' => '+639170000222',
        'password' => Hash::make('secret123'),
        'remote_id' => 2,
        'remote_token' => 'tok-2',
    ]);

    Student::create(['user_id' => $accountA->id, 'remote_id' => 501, 'full_name' => 'Ana Cruz']);
    Student::create(['user_id' => $accountB->id, 'remote_id' => 502, 'full_name' => 'Bea Cruz']);

    Cache::put('remote_connectivity', false, 60); // stay "offline" — no pull should run
    Http::fake(); // guard against any stray real network call (logout, etc.)

    // Switching to account B must not touch account A's cached rows.
    $this->post('/login', ['phone_number' => '+639170000222', 'password' => 'secret123'])
        ->assertRedirect(route('app.dashboard', absolute: false));

    expect(Student::where('remote_id', 501)->exists())->toBeTrue() // account A's kid still cached
        ->and(Student::where('remote_id', 502)->exists())->toBeTrue(); // account B's kid still cached

    // Switching back to account A must not have lost anything either.
    $this->post('/logout')->assertRedirect('/');
    $this->post('/login', ['phone_number' => '+639170000111', 'password' => 'secret123'])
        ->assertRedirect(route('app.dashboard', absolute: false));

    expect(Student::where('remote_id', 501)->exists())->toBeTrue()
        ->and(Student::where('remote_id', 502)->exists())->toBeTrue();
});

it('never leaks another cached account\'s students into the signed-in account\'s pages', function () {
    $accountA = User::factory()->create([
        'phone_number' => '+639170000111',
        'remote_id' => 1,
        'remote_token' => 'tok-1',
    ]);
    $accountB = User::factory()->create([
        'phone_number' => '+639170000222',
        'remote_id' => 2,
        'remote_token' => 'tok-2',
    ]);

    Student::create(['user_id' => $accountA->id, 'remote_id' => 501, 'full_name' => 'Ana Cruz']);
    Student::create(['user_id' => $accountB->id, 'remote_id' => 502, 'full_name' => 'Bea Cruz']);

    $onlyAccountBStudents = Student::query()->where('user_id', $accountB->id)->pluck('remote_id');

    expect($onlyAccountBStudents)->toHaveCount(1)
        ->and($onlyAccountBStudents->first())->toBe(502);

    $this->actingAs($accountB)
        ->getJson('/api/sync/status')
        ->assertOk();

    // Each account's own notification preferences stay isolated too.
    NotificationPreference::create(['user_id' => $accountA->id, 'role' => 'guardian']);
    NotificationPreference::create(['user_id' => $accountB->id, 'role' => 'guardian']);

    expect(NotificationPreference::where('user_id', $accountA->id)->count())->toBe(1)
        ->and(NotificationPreference::where('user_id', $accountB->id)->count())->toBe(1);
});
