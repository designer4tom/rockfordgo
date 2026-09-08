<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('scheduled','pending','accepted','go_to_pickup','confirm_arrival','picked_up','start_ride','dropped_off','completed','cancelled','rejected','no_driver_found') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('scheduled','pending','accepted','go_to_pickup','confirm_arrival','picked_up','start_ride','dropped_off','completed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }
};
