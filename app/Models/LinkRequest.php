<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'relationship', 'sync_status', 'last_error', 'synced_at'])]
class LinkRequest extends Model
{
    const SYNC_STATUS_PENDING = 'pending';
    const SYNC_STATUS_SYNCED = 'synced';
    const SYNC_STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
