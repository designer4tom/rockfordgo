<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surge_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
            $table->json('day_of_week')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('multiplier', 4, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surge_pricing_rules');
    }
};
