<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A queued link request must remember WHICH local account submitted it, so
 * PushLinkRequestJob authenticates the `/api/link/request` call as that user —
 * otherwise the server records the wrong guardian as the code's consumer.
 *
 * Plain nullable column (no FK): SQLite can't add a foreign-key constraint via
 * ALTER TABLE, and this is a short-lived local queue row anyway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('link_requests', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('link_requests', function (Blueprint $table) {
            if (Schema::hasColumn('link_requests', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
