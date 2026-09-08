<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;

class WebsiteController extends Controller
{
    // Each public marketing page renders its own Blade; all editable content is
    // pulled from the CMS via the siteText()/siteList()/siteImage() helpers.
    private const PAGES = [
        'home' => 'website.landing',
        'features' => 'website.features',
        'safety' => 'website.safety',
        'help' => 'website.help',
        'about' => 'website.about',
    ];

    public function show(string $page = 'home')
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        App::setLocale(siteLocale());

        return view(self::PAGES[$page]);
    }

    // Public view of an admin-managed page (Privacy, Terms, …) so it can be
    // linked from the website footer. Prefers the 'common' variant, matching
    // the mobile API's resolution order.
    public function page(string $slug)
    {
        App::setLocale(siteLocale());

        $page = \App\Models\Page::where('slug', $slug)->where('is_active', true)
            ->orderByRaw("FIELD(app_type, 'common') DESC")
            ->firstOrFail();

        return view('website.page', compact('page'));
    }

    // Footer language switcher target.
    public function changeLanguage(string $name)
    {
        $exists = \App\Models\Language::where('name', $name)->where('is_active', true)->exists();

        if ($exists) {
            session(['app_locale' => $name]);
        }

        return back();
    }
}
