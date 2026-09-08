<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surge_pricing_rules', function (Blueprint $table) {
            // Support temporary "emergency" surges that auto-expire.
            $table->boolean('is_manual')->default(false)->after('multiplier');
            $table->timestamp('ends_at')->nullable()->after('is_manual');
        });
    }

    public function down(): void
    {
        Schema::table('surge_pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'ends_at']);
        });
    }
};
