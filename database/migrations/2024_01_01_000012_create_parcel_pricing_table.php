<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_weight', 8, 2);
            $table->decimal('max_weight', 8, 2);
            $table->decimal('base_charge', 10, 2);
            $table->decimal('per_km_charge', 10, 2);
            $table->enum('parcel_type', ['normal', 'fragile', 'document']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_pricing');
    }
};
