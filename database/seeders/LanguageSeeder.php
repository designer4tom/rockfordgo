<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'en', 'title' => 'English', 'language_picture' => 'assets/images/flags/us.png', 'is_default' => true, 'sort_order' => 1],
            ['name' => 'ar', 'title' => 'العربية', 'language_picture' => 'assets/images/flags/sa.png', 'is_default' => false, 'sort_order' => 2],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['name' => $lang['name']], $lang + ['is_active' => true]);
        }
    }
}
