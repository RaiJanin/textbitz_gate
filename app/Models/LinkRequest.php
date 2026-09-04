<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'code', 'relationship', 'sync_status', 'last_error', 'synced_at'])]
class LinkRequest extends Model
{
    const SYNC_STATUS_PENDING = 'pending';
    const SYNC_STATUS_SYNCED = 'synced';
    const SYNC_STATUS_FAILED = 'failed';

    const DEFAULT_RELATIONSHIP = "Guardian";

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    /** The local account that submitted this link request. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
