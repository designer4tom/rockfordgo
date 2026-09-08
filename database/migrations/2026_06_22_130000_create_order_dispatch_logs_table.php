<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-order audit trail of the dispatch process: every driver an order was
 * offered to, who accepted / rejected / didn't respond, and the system events
 * (no driver found, cancelled, reassigned). Lets admins trace exactly what
 * happened to any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            // offered | accepted | rejected | timeout | no_driver_found | cancelled | reassigned
            $table->string('event', 30);
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->decimal('radius_km', 8, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'id']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_dispatch_logs');
    }
};
