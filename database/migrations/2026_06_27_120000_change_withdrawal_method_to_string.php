<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Withdrawal methods are now admin-managed (withdrawal_methods table) and can
 * have any `code`, so the fixed enum('bank','bkash','nagad') on
 * withdrawal_requests.method must become a flexible string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('method', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->enum('method', ['bank', 'bkash', 'nagad'])->change();
        });
    }
};
