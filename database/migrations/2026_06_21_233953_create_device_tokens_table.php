<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FCM device tokens. One owner (customer or driver) can have many tokens
    // (multiple devices). Owner is polymorphic-style via owner_type + owner_id.
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 20);          // user | driver
            $table->unsignedBigInteger('owner_id');
            $table->string('token', 512);
            $table->string('platform', 20)->nullable(); // android | ios | web
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
