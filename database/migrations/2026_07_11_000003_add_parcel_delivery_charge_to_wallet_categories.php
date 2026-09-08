<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Categories used by wallet_transactions.category (ENUM).
    private const WITH_NEW = "'trip_earning','parcel_earning','commission','cod_collection','cod_payout','withdrawal','refund','referral_bonus','top_up','due_payment','parcel_delivery_charge'";

    private const WITHOUT_NEW = "'trip_earning','parcel_earning','commission','cod_collection','cod_payout','withdrawal','refund','referral_bonus','top_up','due_payment'";

    public function up(): void
    {
        // New category: the COD delivery charge billed to the sender's wallet.
        DB::statement('ALTER TABLE wallet_transactions MODIFY category ENUM(' . self::WITH_NEW . ') NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wallet_transactions MODIFY category ENUM(' . self::WITHOUT_NEW . ') NOT NULL');
    }
};
