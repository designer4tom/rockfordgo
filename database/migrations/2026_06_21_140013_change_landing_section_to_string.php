<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The `section` column was an ENUM, so adding new CMS sections (e.g. "stats")
    // required a migration each time. Switch to a plain string for flexibility.
    public function up(): void
    {
        DB::statement("ALTER TABLE landing_page_contents MODIFY section VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE landing_page_contents MODIFY section ENUM('hero','features','how_it_works','app_download','testimonials','faq','contact') NOT NULL");
    }
};
