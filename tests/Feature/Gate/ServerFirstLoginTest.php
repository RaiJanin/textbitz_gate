<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::put('remote_connectivity', true, 60); // device is online
});

it('logs in an account that only exists on the server and caches it locally', function () {
    Http::fake([
        '*/api/login' => Http::response([
            'user' => ['id' => 42, 'name' => 'Rosa Cruz', 'email' => null, 'phone_number' => '+639172223333'],
            'token' => 'srv-token-abc',
        ]),
        '*' => Http::response([], 200),
    ]);

    expect(User::where('phone_number', '+639172223333')->exists())->toBeFalse();

    $this->post('/login', [
        'phone_number' => '+639172223333',
        'password' => 'guardianpass',
    ])->assertRedirect(route('app.dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('phone_number', '+639172223333')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Rosa Cruz')
        ->and($user->remote_id)->toBe(42)
        ->and($user->remote_token)->toBe('srv-token-abc')
        ->and(Hash::check('guardianpass', $user->password))->toBeTrue(); // offline logins now work
});

it('rejects a server-only account with the wrong password', function () {
    Http::fake([
        '*/api/login' => Http::response(['message' => 'Invalid credentials.'], 422),
        '*' => Http::response([], 200),
    ]);

    $this->post('/login', [
        'phone_number' => '+639172224444',
        'password' => 'wrong-pass',
    ])->assertSessionHasErrors('phone_number');

    $this->assertGuest();
    expect(User::where('phone_number', '+639172224444')->exists())->toBeFalse();
});

it('still logs in a normal local account without hitting the server', function () {
    User::factory()->create([
        'phone_number' => '+639172225555',
        'password' => Hash::make('localpass'),
    ]);

    Http::fake(['*' => Http::response([], 500)]); // server would fail if called

    $this->post('/login', [
        'phone_number' => '+639172225555',
        'password' => 'localpass',
    ])->assertRedirect(route('app.dashboard', absolute: false));

    $this->assertAuthenticated();
});
