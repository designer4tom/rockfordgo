<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('appName')) {
    /**
     * Display/brand name shown across the panel and public pages: the
     * admin-configurable "App Name" (Settings → General), falling back to
     * config('app.name') / .env APP_NAME. rescue() keeps error pages
     * rendering even if the database is unavailable.
     */
    function appName(): string
    {
        $name = rescue(fn () => \App\Models\SystemSetting::get('app_name'), null, false);

        return $name ?: config('app.name', 'ReadyRide');
    }
}

if (! function_exists('siteBrand')) {
    /**
     * Public-website brand name: the Website CMS "Brand name"
     * (Website → Shared → Navigation), falling back to appName().
     */
    function siteBrand(): string
    {
        return rescue(fn () => siteText('shared', 'nav', 'brand', appName()), appName(), false);
    }
}

if (! function_exists('adminCan')) {
    /**
     * Determine whether the currently authenticated admin may perform an
     * action on a module. Super admins always pass.
     *
     * @param  string  $module   dashboard|users|drivers|orders|payments|settings|reports|sos|disputes
     * @param  string  $action   read|write|delete
     */
    function adminCan(string $module, string $action = 'read'): bool
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return false;
        }

        return $admin->hasPermission($module, $action);
    }
}

if (! function_exists('adminUser')) {
    /**
     * Convenience accessor for the authenticated admin.
     */
    function adminUser()
    {
        return Auth::guard('admin')->user();
    }
}

if (! function_exists('adminIsRtl')) {
    /**
     * Whether the active admin locale is right-to-left (per the translation manager).
     */
    function adminIsRtl(): bool
    {
        return app(\App\Services\TranslationManager::class)->isRtl(app()->getLocale());
    }
}

if (! function_exists('maskPhone')) {
    /**
     * Mask the middle digits of a phone number for privacy in list views,
     * keeping the leading 3 and trailing 3 digits, e.g. 017****890.
     */
    function maskPhone(?string $phone): string
    {
        if (! $phone) {
            return '—';
        }

        $len = strlen($phone);
        if ($len <= 6) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', $len - 6) . substr($phone, -3);
    }
}

if (! function_exists('phoneValidationRules')) {
    /**
     * Build the phone-number validation rules from the admin-configured regex
     * (Settings → General → "Phone Regex"). Lets each client set the pattern
     * for their own country without touching code. Falls back to a generic
     * international pattern when the setting is empty or not a valid regex.
     *
     * @return array<int, string>
     */
    function phoneValidationRules(bool $required = true): array
    {
        $fallback = '^\+?\d{6,15}$';
        $pattern = trim((string) app(\App\Services\SystemSettingService::class)->get('phone_regex', $fallback));

        // Guard against an invalid/empty pattern saved by mistake.
        if ($pattern === '' || @preg_match('/' . $pattern . '/', '') === false) {
            $pattern = $fallback;
        }

        return array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'regex:/' . $pattern . '/',
        ]);
    }
}

if (! function_exists('siteLocale')) {
    /**
     * Active locale for the public website (footer language switcher sets it).
     * Falls back to the default language, then 'en'.
     */
    function siteLocale(): string
    {
        return session('app_locale')
            ?: (\App\Models\Language::where('is_default', true)->value('name') ?: 'en');
    }
}

if (! function_exists('siteDefaultLocale')) {
    function siteDefaultLocale(): string
    {
        return \App\Models\Language::where('is_default', true)->value('name') ?: 'en';
    }
}

if (! function_exists('siteContentBag')) {
    /**
     * Load (once per request) all site_contents rows for a page, indexed by
     * "locale.section.key" for singles and grouped lists. Cached per page.
     *
     * @return array{single: array, list: array}
     */
    function siteContentBag(string $page): array
    {
        static $cache = [];
        if (isset($cache[$page])) {
            return $cache[$page];
        }

        $single = [];
        $list = [];

        if (\Illuminate\Support\Facades\Schema::hasTable('site_contents')) {
            $rows = \App\Models\SiteContent::where('page', $page)->orderBy('sort_order')->get();
            foreach ($rows as $row) {
                if ($row->type === 'list' || $row->key === 'item') {
                    if (! $row->is_active) {
                        continue;
                    }
                    $list[$row->locale][$row->section][] = (json_decode($row->value, true) ?: []) + ['id' => $row->id];
                } else {
                    $single[$row->locale][$row->section][$row->key] = $row->value;
                }
            }
        }

        return $cache[$page] = ['single' => $single, 'list' => $list];
    }
}

if (! function_exists('siteText')) {
    /**
     * A single CMS text/value for the active locale (default-locale fallback).
     */
    function siteText(string $page, string $section, string $key, string $default = ''): string
    {
        $bag = siteContentBag($page)['single'];
        $loc = siteLocale();
        $def = siteDefaultLocale();

        $val = $bag[$loc][$section][$key] ?? null;
        if ($val === null || $val === '') {
            $val = $bag[$def][$section][$key] ?? null;
        }

        return ($val === null || $val === '') ? $default : $val;
    }
}

if (! function_exists('siteList')) {
    /**
     * Active list items for a CMS list section (active locale, default fallback).
     *
     * @return array<int, array>
     */
    function siteList(string $page, string $section): array
    {
        $bag = siteContentBag($page)['list'];
        $loc = siteLocale();
        $def = siteDefaultLocale();

        return $bag[$loc][$section] ?? $bag[$def][$section] ?? [];
    }
}

if (! function_exists('siteImage')) {
    /**
     * Full URL for a CMS image field. Returns the uploaded image's URL, or the
     * fallback asset path when none is set.
     */
    function siteImage(string $page, string $section, string $key, ?string $fallbackAsset = null): ?string
    {
        $path = siteText($page, $section, $key, '');

        if ($path !== '') {
            return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : \Illuminate\Support\Facades\Storage::url($path);
        }

        return $fallbackAsset ? asset($fallbackAsset) : null;
    }
}

if (! function_exists('integrationStatus')) {
    /**
     * Which third-party integrations still need configuring. Used by the admin
     * setup alert + Setup Guide. Returns [label => configured(bool)].
     */
    function integrationStatus(): array
    {
        $settings = app(\App\Services\SystemSettingService::class);

        return [
            'google_maps' => (bool) ($settings->get('google_maps_key') ?: config('services.google_maps.key')),
            'pusher' => (bool) ($settings->get('pusher_app_id') ?: config('broadcasting.connections.pusher.app_id')),
            'firebase' => is_file(storage_path('app/firebase/firebase.json')),
        ];
    }
}

if (! function_exists('integrationsPending')) {
    // List of integration labels not yet configured (empty = all good).
    function integrationsPending(): array
    {
        $labels = ['google_maps' => 'Google Maps', 'pusher' => 'Pusher (Real-time)', 'firebase' => 'Firebase (Push)'];

        return collect(integrationStatus())
            ->filter(fn ($ok) => ! $ok)
            ->keys()
            ->map(fn ($k) => $labels[$k] ?? $k)
            ->values()
            ->all();
    }
}

if (! function_exists('mapCenter')) {
    /**
     * Default centre for every admin map, from Settings → Map.
     *
     * One definition so a change there moves the dashboard, the zone editor and
     * the zone viewer together. The coordinates below are only a last resort for
     * an install that has never opened the map settings.
     *
     * @return array{lat: float, lng: float, zoom: int}
     */
    function mapCenter(): array
    {
        $settings = app(\App\Services\SystemSettingService::class);

        return [
            'lat' => (float) ($settings->get('map_center_lat') ?: 23.8103),
            'lng' => (float) ($settings->get('map_center_lng') ?: 90.4125),
            'zoom' => (int) ($settings->get('map_zoom') ?: 12),
        ];
    }
}
