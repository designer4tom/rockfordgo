<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('rated_by', ['user', 'driver']);
            $table->unsignedBigInteger('rater_id');
            $table->unsignedBigInteger('ratee_id');
            $table->enum('ratee_type', ['user', 'driver']);
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['ratee_type', 'ratee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
