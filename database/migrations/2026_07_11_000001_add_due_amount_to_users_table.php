<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Outstanding delivery-charge due owed by the sender on COD parcels
            // (mirror of drivers.due_amount). Kept separate from wallet_balance so
            // a sender can hold a balance and a due at the same time.
            $table->decimal('due_amount', 15, 2)->default(0)->after('wallet_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('due_amount');
        });
    }
};
