<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Static legal/info pages (Privacy Policy, Terms, About) — managed from admin.
 * Privacy/Terms differ per app, so (slug + app_type) is unique:
 *   privacy-policy/customer, privacy-policy/driver, terms-conditions/customer, …
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->enum('app_type', ['customer', 'driver', 'common'])->default('common');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['slug', 'app_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
