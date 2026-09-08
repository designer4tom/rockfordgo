<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin table column preferences, keyed by table name:
 *   { "drivers": ["zone", "joined"] }   // the columns that admin hid
 * Nullable, so an admin who never touches it sees every column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admins', 'table_prefs')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->json('table_prefs')->nullable()->after('nav_order');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('admins', 'table_prefs')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('table_prefs');
        });
    }
};
