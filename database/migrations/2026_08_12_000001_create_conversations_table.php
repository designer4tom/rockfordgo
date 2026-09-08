<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ride/parcel chat between the customer and the assigned driver.
 *
 * One conversation per ORDER (order_id is unique) — that is what makes the
 * business rules structural rather than conventional:
 *   • the driver accepting an order opens exactly one conversation;
 *   • completing/cancelling closes it and nothing can reopen it;
 *   • the same customer + driver matched on a NEW order get a NEW conversation,
 *     because the lookup is by order, never by "these two people".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->enum('status', ['active', 'closed'])->default('active');
            // Denormalised for cheap inbox ordering without touching messages.
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
