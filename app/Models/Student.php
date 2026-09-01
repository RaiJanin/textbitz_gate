<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'remote_id', 'full_name', 'grade', 'section', 'avatar_path', 'relationship',
    'school_remote_id', 'school_name', 'school_timezone', 'school_cutoff_time',
    'school_contact_phone', 'school_contact_email', 'synced_at',
])]
class Student extends Model
{
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    public function tapEvents(): HasMany
    {
        return $this->hasMany(TapEvent::class);
    }

    public function scopeByRemoteId($query, int $remoteId)
    {
        return $query->where('remote_id', $remoteId);
    }
}
