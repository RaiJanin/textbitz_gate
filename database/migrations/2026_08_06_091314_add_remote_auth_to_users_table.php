<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'remote_id')) {
                $table->foreignId('remote_id')->nullable()->index();
            }

            if (!Schema::hasColumn('users', 'remote_token')) {
                $table->text('remote_token')->nullable();
            }

            if (!Schema::hasColumn('users', 'remote_token_expires_at')) {
                $table->timestamp('remote_token_expires_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'remote_synced_at')) {
                $table->timestamp('remote_synced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['remote_id', 'remote_token', 'remote_token_expires_at', 'remote_synced_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};