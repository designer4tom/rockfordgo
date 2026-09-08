<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Withdrawals now belong to either a driver OR a customer (user). Add a
    // nullable user_id and make driver_id nullable so the same table serves both.
    public function up(): void
    {
        // driver_id has a FK + NOT NULL — drop FK, make nullable, re-add FK.
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
        });
        DB::statement('ALTER TABLE withdrawal_requests MODIFY driver_id BIGINT UNSIGNED NULL');
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->after('driver_id')->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
