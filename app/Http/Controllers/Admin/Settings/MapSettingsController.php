<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;

class MapSettingsController extends Controller implements HasMiddleware
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index']),
            new Middleware('permission:settings,write', only: ['update', 'testKey']),
        ];
    }

    private const KEYS = [
        'google_maps_key', 'map_center_lat', 'map_center_lng', 'map_zoom', 'map_type',
        'location_update_interval', 'track_driver_when',
        'search_radius_km', 'radius_expand_km', 'radius_expand_seconds', 'max_radius_km', 'max_dispatch_attempts',
        'zone_mode',
    ];

    public function index()
    {
        $s = fn ($k, $d = null) => $this->settings->get($k, $d);

        return view('admin.settings.map', [
            'settings' => [
                'google_maps_key' => $s('google_maps_key', ''),
                // Same fallback as every admin map — see mapCenter().
                'map_center_lat' => $s('map_center_lat', (string) mapCenter()['lat']),
                'map_center_lng' => $s('map_center_lng', (string) mapCenter()['lng']),
                'map_zoom' => $s('map_zoom', '12'),
                'map_type' => $s('map_type', 'roadmap'),
                'location_update_interval' => $s('location_update_interval', '5'),
                'track_driver_when' => $s('track_driver_when', 'online'),
                'search_radius_km' => $s('search_radius_km', '5'),
                'auto_expand_radius' => $this->settings->getBool('auto_expand_radius', true),
                'radius_expand_km' => $s('radius_expand_km', '2'),
                'radius_expand_seconds' => $s('radius_expand_seconds', '30'),
                'max_radius_km' => $s('max_radius_km', '15'),
                'max_dispatch_attempts' => $s('max_dispatch_attempts', '10'),
                'zone_mode' => $s('zone_mode', 'flexible'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'map_center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'map_center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'map_zoom' => ['nullable', 'integer', 'between:8,18'],
            'map_type' => ['nullable', 'in:roadmap,satellite,hybrid'],
            'location_update_interval' => ['nullable', 'in:3,5,10'],
            'track_driver_when' => ['nullable', 'in:online,trip'],
            'search_radius_km' => ['nullable', 'numeric', 'between:1,20'],
            'max_radius_km' => ['nullable', 'numeric', 'between:1,50'],
            'max_dispatch_attempts' => ['nullable', 'integer', 'between:1,50'],
            'zone_mode' => ['nullable', 'in:strict,flexible'],
        ]);

        foreach (self::KEYS as $key) {
            if ($request->filled($key)) {
                $this->settings->set($key, (string) $request->input($key), 'map');
            }
        }

        $this->settings->set('auto_expand_radius', $request->boolean('auto_expand_radius') ? 'true' : 'false', 'map');

        return back()->with('success', 'Map settings saved.');
    }

    // AJAX: verify the Google Maps key with a simple geocode request.
    public function testKey()
    {
        $key = $this->settings->get('google_maps_key');
        if (! $key) {
            return response()->json(['ok' => false, 'message' => 'No Google Maps key configured.'], 422);
        }

        try {
            $res = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => 'Dhaka',
                'key' => $key,
            ]);
            $status = $res->json('status');

            return response()->json([
                'ok' => $status === 'OK',
                'message' => $status === 'OK' ? 'Key is valid.' : ('Google returned: ' . ($res->json('error_message') ?? $status)),
            ], $status === 'OK' ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Request failed: ' . $e->getMessage()], 422);
        }
    }
}
