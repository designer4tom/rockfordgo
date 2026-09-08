<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'drivers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('emergency_contact_name')->nullable();
                $t->string('emergency_contact_phone')->nullable();
                $t->string('emergency_contact_relationship')->nullable();
                $t->timestamp('deletion_requested_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'drivers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'deletion_requested_at']);
            });
        }
    }
};
