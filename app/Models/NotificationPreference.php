<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['role', 'arrival', 'departure', 'late_alert', 'weekly_summary', 'sync_status', 'synced_at'])]
class NotificationPreference extends Model
{
    const ROLE_GUARDIAN = 'guardian';
    const ROLE_STUDENT = 'student';

    const SYNC_STATUS_PENDING = 'pending';
    const SYNC_STATUS_SYNCED = 'synced';
    const SYNC_STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'arrival' => 'boolean',
            'departure' => 'boolean',
            'late_alert' => 'boolean',
            'weekly_summary' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
