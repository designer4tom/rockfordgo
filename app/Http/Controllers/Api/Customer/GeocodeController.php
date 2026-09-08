<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Server-side proxy for Google geocoding so the Maps key stays off the device.
 */
class GeocodeController extends Controller
{
    use ApiResponse;

    public function __construct(private SystemSettingService $settings)
    {
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $key = $this->settings->get('google_maps_key')
            ?: config('services.google_maps.server_key')
            ?: config('services.google_maps.key');
        if (! $key) {
            return $this->error('Address search is not available right now.', 503);
        }

        try {
            $params = ['input' => $data['q'], 'key' => $key];
            if (! empty($data['lat']) && ! empty($data['lng'])) {
                $params['location'] = $data['lat'] . ',' . $data['lng'];
                $params['radius'] = 50000;
            }

            $res = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);
            $predictions = collect($res->json('predictions', []))->map(fn ($p) => [
                'address' => $p['description'] ?? null,
                'place_id' => $p['place_id'] ?? null,
            ])->all();

            return $this->success($predictions, 'Search results.');
        } catch (\Throwable $e) {
            return $this->error('Address search failed.', 502);
        }
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'place_id' => ['required', 'string'],
        ]);

        $key = $this->settings->get('google_maps_key')
            ?: config('services.google_maps.server_key')
            ?: config('services.google_maps.key');
        if (! $key) {
            return $this->error('Geocoding is not available right now.', 503);
        }

        try {
            $res = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'place_id' => $data['place_id'],
                'key' => $key,
            ]);
            $first = $res->json('results.0');
            if (! $first) {
                return $this->error('Location not found.', 404);
            }
            $loc = $first['geometry']['location'] ?? [];

            return $this->success([
                'address' => $first['formatted_address'] ?? null,
                'lat' => (float) ($loc['lat'] ?? 0),
                'lng' => (float) ($loc['lng'] ?? 0),
                'place_id' => $data['place_id'],
            ], 'Place details.');
        } catch (\Throwable $e) {
            return $this->error('Geocoding failed.', 502);
        }
    }

    public function reverse(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $key = $this->settings->get('google_maps_key')
            ?: config('services.google_maps.server_key')
            ?: config('services.google_maps.key');
        if (! $key) {
            return $this->error('Reverse geocoding is not available right now.', 503);
        }

        try {
            $res = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $data['lat'] . ',' . $data['lng'],
                'key' => $key,
            ]);
            $first = $res->json('results.0');

            return $this->success([
                'address' => $first['formatted_address'] ?? null,
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
                'place_id' => $first['place_id'] ?? null,
            ], 'Reverse geocode result.');
        } catch (\Throwable $e) {
            return $this->error('Reverse geocoding failed.', 502);
        }
    }
}
