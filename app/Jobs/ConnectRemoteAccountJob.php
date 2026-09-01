<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Remote\RemoteAuthService;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConnectRemoteAccountJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public array $backoff = [10, 30, 60, 120, 300];

    public function retryUntil()
    {
        return now()->addDays(3);
    }

    public function __construct(
        public User $user,
        public string $password,
        public bool $isNewRegistration = false,
    ) {}

    public function handle(): void
    {
        if (!ServerConnectivityService::isOnline()) {
            Log::info('Server offline, holding remote auth connection', ['user_id' => $this->user->id]);
            $this->release(30);
            return;
        }

        $connected = $this->isNewRegistration
            ? RemoteAuthService::register($this->user, $this->password)
            : RemoteAuthService::login($this->user, $this->password);

        if (!$connected) {
            $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Remote account connect permanently failed', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
