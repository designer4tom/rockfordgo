<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
            $table->enum('type', ['ride', 'parcel']);
            $table->enum('status', [
                'scheduled', 'pending', 'accepted', 'go_to_pickup', 'confirm_arrival',
                'picked_up', 'start_ride', 'dropped_off', 'completed', 'cancelled', 'rejected',
            ])->default('pending');

            // Locations
            $table->text('pickup_address');
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->text('drop_address');
            $table->decimal('drop_lat', 10, 8);
            $table->decimal('drop_lng', 11, 8);
            $table->json('stops')->nullable();

            // Ride
            $table->string('otp', 6)->nullable();
            $table->timestamp('otp_verified_at')->nullable();

            // Parcel
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->enum('parcel_type', ['normal', 'fragile', 'document'])->nullable();
            $table->decimal('parcel_weight', 8, 2)->nullable();
            $table->enum('parcel_size', ['small', 'medium', 'large'])->nullable();
            $table->string('parcel_photo')->nullable();
            $table->text('parcel_note')->nullable();
            $table->boolean('is_cod')->default(false);
            $table->decimal('cod_amount', 15, 2)->nullable();
            $table->enum('payment_timing', ['before', 'after'])->nullable();
            $table->enum('proof_type', ['otp', 'photo', 'signature'])->nullable();
            $table->string('proof_data')->nullable();
            $table->timestamp('proof_collected_at')->nullable();

            // Pricing
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('distance_charge', 10, 2)->nullable();
            $table->decimal('time_charge', 10, 2)->nullable();
            $table->decimal('surge_multiplier', 4, 2)->default(1);
            $table->decimal('surge_amount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->decimal('coupon_discount', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('admin_commission', 10, 2)->nullable();
            $table->decimal('driver_earning', 10, 2)->nullable();
            $table->decimal('tip_amount', 10, 2)->default(0);

            // Payment
            $table->enum('payment_method', ['cash', 'online', 'wallet', 'cod']);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('payment_intent_id')->nullable();

            // Schedule
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('driver_assigned_at')->nullable();

            // Cancel
            $table->enum('cancelled_by', ['user', 'driver', 'admin'])->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('cancellation_fee', 10, 2)->default(0);

            // Status timestamps
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
