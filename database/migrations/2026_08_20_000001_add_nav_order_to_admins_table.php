<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin sidebar ordering. Nullable, so an admin who never reorders
 * anything keeps the default layout and nothing has to be backfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admins', 'nav_order')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->json('nav_order')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('admins', 'nav_order')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('nav_order');
        });
    }
};
