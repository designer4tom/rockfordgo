<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver wallet recharge requests. A recharge first clears any outstanding due,
 * then the remainder lands in the wallet balance (see WalletService::processDriverRecharge).
 * Created via the driver app (gateway flow) or by an admin (manual, admin_id set).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('payment_url')->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->decimal('due_cleared', 10, 2)->default(0);
            $table->decimal('balance_added', 10, 2)->default(0);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_requests');
    }
};
