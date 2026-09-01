<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local offline-first cache of the attendance data owned by the server. Every
 * table keeps the remote_id / synced_at pattern the sync layer depends on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remote_id')->unique();
            $table->string('full_name');
            $table->string('grade')->nullable();
            $table->string('section')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('school_remote_id')->nullable();
            $table->string('school_name')->nullable();
            $table->string('school_timezone')->default('Asia/Manila');
            $table->string('school_cutoff_time')->nullable();
            $table->string('school_contact_phone')->nullable();
            $table->string('school_contact_email')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remote_id')->unique();
            $table->string('name');
            $table->string('status')->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tap_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remote_id')->nullable()->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('gate_name')->nullable();
            $table->string('direction');
            $table->timestamp('tapped_at');
            $table->boolean('is_late')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'tapped_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->boolean('arrival')->default(true);
            $table->boolean('departure')->default(true);
            $table->boolean('late_alert')->default(true);
            $table->boolean('weekly_summary')->default(true);
            $table->string('sync_status')->default('synced');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique('role');
        });

        Schema::create('link_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('relationship')->nullable();
            $table->string('sync_status')->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_requests');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('tap_events');
        Schema::dropIfExists('gates');
        Schema::dropIfExists('students');
    }
};
