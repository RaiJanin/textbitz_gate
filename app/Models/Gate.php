<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['remote_id', 'name', 'status', 'last_seen_at', 'synced_at'])]
class Gate extends Model
{
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
