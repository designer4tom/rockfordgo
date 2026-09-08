<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page', 40);            // shared | home | features | safety | help | about
            $table->string('locale', 8);           // en | ar | ...
            $table->string('section', 60);         // hero | features | footer_columns | ...
            $table->string('key', 60)->default('item'); // 'item' for list rows, else field key
            $table->string('type', 20)->default('text'); // text | image | list
            $table->longText('value')->nullable(); // text, image path, or JSON for list items
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['page', 'locale', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
