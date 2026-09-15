<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'user_id', 'remote_id', 'full_name', 'grade', 'section', 'avatar_path', 'relationship',
    'relationship_pending',
    'school_remote_id', 'school_name', 'school_timezone', 'school_cutoff_time',
    'school_contact_phone', 'school_contact_email', 'synced_at',
])]
class Student extends Model
{
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
            'relationship_pending' => 'boolean',
        ];
    }

    /** The local account this cached student belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tapEvents(): HasMany
    {
        return $this->hasMany(TapEvent::class);
    }

    public function scopeByRemoteId(Builder $query, int $remoteId)
    {
        return $query->where('remote_id', $remoteId);
    }

    /** Rows cached for a specific local account. */
    public function scopeOwnedBy(Builder $query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** Rows cached for the currently authenticated local account. */
    public function scopeMine(Builder $query)
    {
        return $query->where('user_id', Auth::id());
    }
}
