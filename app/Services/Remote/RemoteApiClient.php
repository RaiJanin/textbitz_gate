<?php

namespace App\Services\Remote;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RemoteApiClient
{
    public const RESULT_SUCCESS = 'success';
    public const RESULT_RETRY = 'retry';
    public const RESULT_FAILED = 'failed';
    public const RESULT_UNAUTHORIZED = 'unauthorized';

    public static function get(User $user, string $endpoint, array $query = []): array
    {
        $token = $user->remote_token;
        if (!$token) 
        {
            return [
                'result' => self::RESULT_UNAUTHORIZED,
                'message' => 'No remote token for user',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->get(self::url($endpoint), $query);

            return self::classify($response);
        } catch (ConnectionException $e) {
            return [
                'result' => self::RESULT_RETRY,
                'message' => $e->getMessage(),
            ];
        }
    }


    public static function post(User $user, string $endpoint, array $payload): array
    {
        $token = $user->remote_token;

        if (!$token) {
            return [
                'result' => self::RESULT_UNAUTHORIZED,
                'message' => 'No remote token for user',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post(self::url($endpoint), $payload);

            return self::classify($response);

        } catch (ConnectionException $e) {
            return [
                'result' => self::RESULT_RETRY,
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function delete(User $user, string $endpoint, array $payload = []): array
    {
        $token = $user->remote_token;

        if (!$token) {
            return [
                'result' => self::RESULT_UNAUTHORIZED,
                'message' => 'No remote token for user',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->delete(self::url($endpoint), $payload);

            return self::classify($response);

        } catch (ConnectionException $e) {
            return [
                'result' => self::RESULT_RETRY,
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function put(User $user, string $endpoint, array $payload): array
    {
        $token = $user->remote_token;

        if (!$token) {
            return [
                'result' => self::RESULT_UNAUTHORIZED,
                'message' => 'No remote token for user',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->put(self::url($endpoint), $payload);

            return self::classify($response);

        } catch (ConnectionException $e) {
            return [
                'result' => self::RESULT_RETRY,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected static function classify(Response $response): array
    {
        if ($response->successful()) {
            return [
                'result' => self::RESULT_SUCCESS,
                'data' => $response->json(),
            ];
        }

        if ($response->status() === 401) {
            return [
                'result' => self::RESULT_UNAUTHORIZED,
                'message' => 'Remote token invalid or expired',
                'status' => 401,
            ];
        }

        if ($response->clientError()) {
            return [
                'result' => self::RESULT_FAILED,
                'message' => 'Server rejected request',
                'data' => $response->json(),
                'status' => $response->status(),
            ];
        }

        // 5xx or anything else — worth retrying
        return [
            'result' => self::RESULT_RETRY,
            'message' => 'Server error',
            'status' => $response->status(),
        ];
    }

    protected static function url(string $endpoint): string
    {
        return rtrim(config('services.textbitz.server_url'), '/') . '/' . ltrim($endpoint, '/');
    }
}