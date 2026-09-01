<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['remote_id', 'student_id', 'gate_name', 'direction', 'tapped_at', 'is_late', 'synced_at'])]
class TapEvent extends Model
{
    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

    protected function casts(): array
    {
        return [
            'tapped_at' => 'datetime',
            'synced_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
