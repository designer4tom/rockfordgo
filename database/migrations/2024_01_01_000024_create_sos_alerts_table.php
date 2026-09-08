<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('triggered_by', ['user', 'driver']);
            $table->unsignedBigInteger('triggered_by_id');
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->foreignId('acknowledged_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_alerts');
    }
};
