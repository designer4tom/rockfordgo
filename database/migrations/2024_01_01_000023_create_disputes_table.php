<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('raised_by', ['user', 'driver']);
            $table->unsignedBigInteger('raised_by_id');
            $table->enum('category', [
                'driver_behavior', 'route_issue', 'overcharging',
                'parcel_issue', 'payment_issue', 'other',
            ]);
            $table->text('description');
            $table->enum('status', ['open', 'under_review', 'resolved'])->default('open');
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('refund_issued')->default(false);
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
