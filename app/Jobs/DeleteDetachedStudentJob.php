<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Removes a student from the local cache once the server has confirmed the
 * guardian can no longer view them — an admin detached the link on the
 * server. A purely local cleanup (no network call), so this is always run
 * with dispatchSync() right where the 403 was observed.
 */
class DeleteDetachedStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Student $student) {}

    public function handle(): void
    {
        Log::info('Removing locally cached student — no longer linked to this guardian', [
            'student_id' => $this->student->id,
            'remote_id' => $this->student->remote_id,
        ]);

        // Cascades to tap_events via the FK on that table.
        $this->student->delete();
    }
}
