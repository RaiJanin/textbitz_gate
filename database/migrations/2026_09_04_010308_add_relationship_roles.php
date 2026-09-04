<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardian↔student relationship ('Parent' | 'Guardian'):
 *  - `users.active_role`         — the guardian's default (was unused).
 *  - `students.relationship_pending` — an offline per-student change not yet
 *    confirmed by the server; guards it from being clobbered by the /api/me sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        User::query()->whereNull('active_role')->update(['active_role' => 'Guardian']);
        User::query()->where('active_role', 'guardian')->update(['active_role' => 'Guardian']);

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('relationship_pending')->default(false)->after('relationship');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('relationship_pending');
        });
    }
};
