<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            // Same owner_type/owner_id convention used by wallet_transactions
            // and device_tokens — the sender is a customer or a driver.
            $table->enum('sender_type', ['user', 'driver']);
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Cursor pagination (WHERE conversation_id = ? AND id < ? ORDER BY id DESC).
            $table->index(['conversation_id', 'id']);
            // Unread counting for the recipient.
            $table->index(['conversation_id', 'sender_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
