<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['user_id', 'role', 'arrival', 'departure', 'late_alert', 'weekly_summary', 'sync_status', 'synced_at'])]
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

    /** The local account this preference row belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rows belonging to a specific local account. */
    public function scopeOwnedBy(Builder $query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Rows belonging to the currently authenticated local account. */
    public function scopeMine(Builder $query)
    {
        return $query->where('user_id', Auth::id());
    }
}
