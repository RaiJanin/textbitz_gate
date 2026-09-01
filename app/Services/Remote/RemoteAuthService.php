<?php

namespace App\Services\Remote;

use App\Models\User;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RemoteAuthService extends RemoteApiClient
{
    public static function authenticateOrDefer(User $user, string $password): void
    {
        if (ServerConnectivityService::isOnline()) {
            if (self::login($user, $password)) {
                Cache::forget(self::pendingAuthKey($user));
                return;
            }
        }

        self::deferRemoteAuth($user, $password);
    }

    public static function flushPendingAuth(): void
    {
        if (!ServerConnectivityService::isOnline()) {
            return;
        }

        User::each(function (User $user) {
            $key = self::pendingAuthKey($user);
            $encrypted = Cache::get($key);

            if (!$encrypted) {
                return;
            }

            try {
                $password = decrypt($encrypted);
            } catch (\Exception $e) {
                Log::error('Could not decrypt pending remote credential', ['user_id' => $user->id]);
                Cache::forget($key);
                return;
            }

            $success = self::login($user, $password);

            Cache::forget($key);

            if (!$success) {
                Log::warning('Deferred remote auth failed on reconnect', ['user_id' => $user->id]);
            }
        });
    }

    public static function register(User $user, string $password): bool
    {
        $response = Http::post(self::url('/api/register'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'password' => $password,
            'device_name' => php_uname('n'),
        ]);

        if (!$response->successful()) {
            Log::error('Remote registration failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);
            return false;
        }

        $user->update([
            'remote_id' => $response->json('user.id'),
            'remote_token' => $response->json('token'),
            'remote_synced_at' => now(),
        ]);

        return true;
    }

    public static function login(User $user, string $password): bool
    {
        $response = Http::post(self::url('/api/login'), [
            'phone_number' => $user->phone_number,
            'password' => $password,
            'device_name' => php_uname('n'),
        ]);

        if (!$response->successful()) {
            Log::error('Remote login failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);
            return false;
        }

        $user->update([
            'remote_id' => $response->json('user.id'),
            'remote_token' => $response->json('token'),
            'remote_synced_at' => now(),
        ]);

        return true;
    }

    public static function verify(User $user): bool
    {
        if (!$user->remote_token) {
            return false;
        }

        $response = Http::withToken($user->remote_token)
            ->get(self::url('/api/user'));

        if ($response->successful()) {
            return true;
        }

        if ($response->status() === 401) {
            $user->update(['remote_token' => null]);
        }

        return false;
    }

    public static function logout(User $user): void
    {
        if ($user->remote_token)
        {
            Http::withToken($user->remote_token)->post(self::url('/api/logout'));

            static::invalidateSession($user);
        }
    }

    public static function invalidateSession(User $user): void
    {
        if(!empty($user->remote_token))
        {
            $user->update([
                'remote_token' => null,
                'remote_synced_at' => null,
            ]);

            Log::info('Remote session invalidated locally', ['user_id' => $user->id]);
        }
    }

    public static function verifyAllTokens(): void
    {
        if (!ServerConnectivityService::isOnline()) {
            Log::info('Skipping bulk token verification — server unreachable');
            return;
        }

        User::whereNotNull('remote_token')->each(fn (User $user) => self::verify($user));
    }

    protected static function pendingAuthKey(User $user): string
    {
        return "remote_auth_pending:{$user->id}";
    }

    protected static function deferRemoteAuth(User $user, string $password): void
    {
        Cache::put(
            self::pendingAuthKey($user),
            encrypt($password),
            now()->addHours(6)
        );

        Log::info('Remote auth deferred — will retry on reconnect', ['user_id' => $user->id]);
    }

    
}