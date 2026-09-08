<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gateway checkout URLs (e.g. Stripe) can exceed 255 chars, so payment_url
 * needs to be TEXT instead of VARCHAR(255).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharge_requests', function (Blueprint $table) {
            $table->text('payment_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('recharge_requests', function (Blueprint $table) {
            $table->string('payment_url')->nullable()->change();
        });
    }
};
