<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ServerConnectionRestored
{
    use Dispatchable;

    public function __construct(public readonly \DateTimeInterface $restoredAt) {}
}
