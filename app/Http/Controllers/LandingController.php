<?php

namespace App\Http\Controllers;

use App\Models\LandingPageContent;
use App\Services\SystemSettingService;

class LandingController extends Controller
{
    public function index(SystemSettingService $settings)
    {
        $single = [];
        foreach (['hero', 'app_download', 'contact'] as $section) {
            $single[$section] = LandingPageContent::where('section', $section)->pluck('value', 'key')->toArray();
        }

        $lists = [];
        foreach (['features', 'stats', 'how_it_works', 'testimonials', 'faq'] as $section) {
            $lists[$section] = LandingPageContent::where('section', $section)
                ->where('is_active', true)
                ->orderBy('sort_order')->get()
                ->map(fn ($row) => json_decode($row->value, true) ?: []);
        }

        return view('landing.index', [
            'single' => $single,
            'lists' => $lists,
            'seo' => $settings->getGroup('seo'),
            'appName' => $settings->get('app_name', config('app.name', 'ReadyRide')),
            'logo' => $settings->get('app_logo'),
        ]);
    }
}
