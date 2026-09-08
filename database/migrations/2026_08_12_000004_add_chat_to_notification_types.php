<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chat messages raise an in-app/push notification, so 'chat' has to be a valid
 * notifications.type — otherwise the insert fails with "Data truncated".
 */
return new class extends Migration
{
    private const WITH = "'order_request','order_update','payment','document_expiry','due_warning','withdrawal','sos','dispute','broadcast','promo','chat'";

    private const WITHOUT = "'order_request','order_update','payment','document_expiry','due_warning','withdrawal','sos','dispute','broadcast','promo'";

    public function up(): void
    {
        DB::statement('ALTER TABLE notifications MODIFY type ENUM(' . self::WITH . ') NOT NULL');
    }

    public function down(): void
    {
        DB::table('notifications')->where('type', 'chat')->delete();
        DB::statement('ALTER TABLE notifications MODIFY type ENUM(' . self::WITHOUT . ') NOT NULL');
    }
};
