<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat presence ("Online" / "last seen 5m ago").
 *
 * Deliberately a timestamp rather than a boolean:
 *   • drivers.is_online already means "available to receive orders" — reusing it
 *     for chat would take a driver out of dispatch just for idling in a chat;
 *   • a heartbeat timestamp self-heals, whereas a boolean stays stuck on
 *     "online" forever if the app crashes without calling /offline.
 * Online is derived as: last_seen_at within the heartbeat window.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'drivers'] as $table) {
            if (! Schema::hasColumn($table, 'last_seen_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->timestamp('last_seen_at')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'drivers'] as $table) {
            if (Schema::hasColumn($table, 'last_seen_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('last_seen_at');
                });
            }
        }
    }
};
