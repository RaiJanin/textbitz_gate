<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scopes the local read-cache per local account instead of wiping it on every
 * account switch. Plain nullable columns (no FK): SQLite can't add a
 * foreign-key constraint via ALTER TABLE, same reasoning as
 * add_user_id_to_link_requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_preferences', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['remote_id']);
            $table->unique(['user_id', 'remote_id']);
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropUnique(['role']);
            $table->unique(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'role']);
            $table->unique('role');

            if (Schema::hasColumn('notification_preferences', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'remote_id']);
            $table->unique('remote_id');

            if (Schema::hasColumn('students', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
