<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeneralSettingsController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['update']),
        ];
    }

    // Business identity / currency moved to Business Settings. General holds
    // branding, contact & social, app-store links, and maintenance.
    private const TEXT_KEYS = [
        'support_email', 'support_phone', 'support_whatsapp', 'support_hours', 'office_address',
        'social_facebook', 'social_instagram', 'social_youtube', 'social_website',
        'customer_app_store', 'customer_play_store', 'driver_app_store', 'driver_play_store',
        'maintenance_message',
    ];

    public function index()
    {
        $s = fn ($k, $d = null) => $this->settings->get($k, $d);

        return view('admin.settings.general', [
            'settings' => [
                'app_name' => $s('app_name', config('app.name')),
                'app_logo' => $s('app_logo'),
                'app_favicon' => $s('app_favicon'),
                'admin_logo' => $s('admin_logo'),
                'support_email' => $s('support_email', ''),
                'support_phone' => $s('support_phone', ''),
                'support_whatsapp' => $s('support_whatsapp', ''),
                'support_hours' => $s('support_hours', ''),
                'office_address' => $s('office_address', ''),
                'social_facebook' => $s('social_facebook', ''),
                'social_instagram' => $s('social_instagram', ''),
                'social_youtube' => $s('social_youtube', ''),
                'social_website' => $s('social_website', ''),
                'customer_app_store' => $s('customer_app_store', ''),
                'customer_play_store' => $s('customer_play_store', ''),
                'driver_app_store' => $s('driver_app_store', ''),
                'driver_play_store' => $s('driver_play_store', ''),
                'maintenance_mode' => $this->settings->getBool('maintenance_mode', false),
                'maintenance_message' => $s('maintenance_message', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            'support_hours' => ['nullable', 'string', 'max:100'],
            'office_address' => ['nullable', 'string', 'max:255'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_youtube' => ['nullable', 'string', 'max:255'],
            'social_website' => ['nullable', 'string', 'max:255'],
            'customer_app_store' => ['nullable', 'string', 'max:255'],
            'customer_play_store' => ['nullable', 'string', 'max:255'],
            'driver_app_store' => ['nullable', 'string', 'max:255'],
            'driver_play_store' => ['nullable', 'string', 'max:255'],
            'maintenance_message' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'image', 'max:2048'],
            'app_favicon' => ['nullable', 'image', 'max:1024'],
            'admin_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        foreach (self::TEXT_KEYS as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, (string) $request->input($key), 'general');
            }
        }

        $this->settings->set('maintenance_mode', $request->boolean('maintenance_mode') ? 'true' : 'false', 'general');

        foreach (['app_logo', 'app_favicon', 'admin_logo'] as $img) {
            if ($request->hasFile($img)) {
                if ($old = $this->settings->get($img)) {
                    Storage::disk('public')->delete($old);
                }
                $path = $request->file($img)->store('branding', 'public');
                $this->settings->set($img, $path, 'general');
            }
        }

        // Optional removal of the admin panel logo → revert to the default badge.
        // (A newly uploaded file always wins over a remove request.)
        if ($request->boolean('remove_admin_logo') && ! $request->hasFile('admin_logo')) {
            if ($old = $this->settings->get('admin_logo')) {
                Storage::disk('public')->delete($old);
            }
            $this->settings->set('admin_logo', null, 'general');
        }

        // Refresh the public /config cache so changes show immediately in the apps.
        Cache::forget('api_config');

        return back()->with('success', 'General settings saved.');
    }
}
