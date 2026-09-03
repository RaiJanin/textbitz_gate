<?php

use App\Listeners\RegisterPushToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Events\PushNotification\TokenGenerated;

it('stores the FCM token locally and forwards it to the server', function () {
    Http::fake(['*/api/device-tokens' => Http::response(['registered' => true], 201)]);

    $user = User::factory()->create([
        'phone_number' => '+639170000900',
        'remote_id' => 1,
        'remote_token' => 'tok',
    ]);

    $this->actingAs($user)
        ->postJson('/api/device-tokens', ['token' => 'fcm-abc-123', 'platform' => 'android'])
        ->assertOk()
        ->assertJsonPath('registered', true);

    expect($user->fresh()->fcm_token)->toBe('fcm-abc-123');

    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/device-tokens') && $r['token'] === 'fcm-abc-123');
});

it('an unauthenticated device-token call returns JSON 401, not a redirect', function () {
    $this->postJson('/api/device-tokens', ['token' => 'x'])->assertUnauthorized();
});

it('the TokenGenerated listener no-ops when not running natively', function () {
    User::factory()->create(['phone_number' => '+639170000901']);

    (new RegisterPushToken)->handle(new TokenGenerated('fcm-xyz'));

    expect(User::first()->fcm_token)->toBeNull();
});

it('deregisters the push token from the server on logout', function () {
    \Illuminate\Support\Facades\Cache::put('remote_connectivity', true, 60);
    Http::fake([
        '*/api/device-tokens' => Http::response(['removed' => true]),
        '*/api/logout' => Http::response([], 204),
        '*' => Http::response([], 200),
    ]);

    $user = User::factory()->create([
        'phone_number' => '+639170000902',
        'remote_id' => 1,
        'remote_token' => 'tok',
        'fcm_token' => 'fcm-logout-1',
    ]);

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    Http::assertSent(fn ($r) => $r->method() === 'DELETE'
        && str_contains($r->url(), '/api/device-tokens'));

    expect($user->fresh()->fcm_token)->toBeNull();
    $this->assertGuest();
});

it('logout without an fcm token skips the deregister call', function () {
    \Illuminate\Support\Facades\Cache::put('remote_connectivity', true, 60);
    Http::fake(['*' => Http::response([], 200)]);

    $user = User::factory()->create([
        'phone_number' => '+639170000903',
        'remote_id' => 1,
        'remote_token' => 'tok',
        'fcm_token' => null,
    ]);

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    Http::assertNotSent(fn ($r) => $r->method() === 'DELETE' && str_contains($r->url(), '/api/device-tokens'));
});
