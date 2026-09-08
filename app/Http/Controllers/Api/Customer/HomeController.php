<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Service;
use App\Models\VehicleCategory;
use App\Services\GeoService;
use App\Services\PricingService;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private GeoService $geo,
        private PricingService $pricing,
        private SystemSettingService $settings,
    ) {
    }

    public function services()
    {
        $services = Service::where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'type' => $s->type,
                'icon' => $s->icon ? asset(Storage::url($s->icon)) : null,
                'description' => $s->description,
            ]);

        return $this->success($services, 'Services fetched.');
    }

    public function vehicleCategories(Request $request)
    {
        $data = $request->validate([
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
        ]);

        $radius = (float) $this->settings->get('search_radius_km', 5);

        $categories = VehicleCategory::where('is_active', true)
            ->whereHas('service', fn ($q) => $q->where('type', 'ride')->where('is_active', true))
            ->orderBy('sort_order')
            ->get()
            ->map(function (VehicleCategory $c) use ($data, $radius) {
                $drivers = Driver::query()->online()->approved()
                    ->whereHas('vehicles', fn ($v) => $v->where('vehicle_category_id', $c->id))
                    ->withinRadius((float) $data['pickup_lat'], (float) $data['pickup_lng'], $radius)
                    ->orderByDistance((float) $data['pickup_lat'], (float) $data['pickup_lng'])
                    ->get(['id', 'current_lat', 'current_lng']);

                $nearest = $drivers->first();
                $eta = $nearest
                    ? $this->geo->estimateMinutes($this->geo->distanceKm((float) $data['pickup_lat'], (float) $data['pickup_lng'], (float) $nearest->current_lat, (float) $nearest->current_lng))
                    : null;

                $multiplier = $this->pricing->getActiveSurgeMultiplier($c->id);

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    // Full URL — the app feeds this straight to Image.network.
                    'icon' => $c->icon ? asset(Storage::url($c->icon)) : null,
                    'capacity' => $c->capacity,
                    'base_fare' => number_format((float) $c->base_fare, 2, '.', ''),
                    'per_km_rate' => number_format((float) $c->per_km_rate, 2, '.', ''),
                    'per_minute_rate' => number_format((float) $c->per_minute_rate, 2, '.', ''),
                    'minimum_fare' => number_format((float) $c->minimum_fare, 2, '.', ''),
                    'estimated_arrival' => $eta,
                    'available_drivers' => $drivers->count(),
                    'surge_active' => $multiplier > 1,
                    'surge_multiplier' => $multiplier,
                ];
            });

        return $this->success($categories, 'Vehicle categories fetched.');
    }

    public function fareEstimate(Request $request)
    {
        $data = $request->validate([
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'drop_lat' => ['required', 'numeric'],
            'drop_lng' => ['required', 'numeric'],
            'stops' => ['nullable', 'array'],
            'stops.*.lat' => ['required_with:stops', 'numeric'],
            'stops.*.lng' => ['required_with:stops', 'numeric'],
        ]);

        $category = VehicleCategory::findOrFail($data['vehicle_category_id']);

        $points = [[$data['pickup_lat'], $data['pickup_lng']]];
        foreach ($data['stops'] ?? [] as $stop) {
            $points[] = [$stop['lat'], $stop['lng']];
        }
        $points[] = [$data['drop_lat'], $data['drop_lng']];

        $distanceKm = $this->geo->routeDistanceKm($points);
        $minutes = $this->geo->estimateMinutes($distanceKm);
        $fare = $this->pricing->calculateRideFare($category, $distanceKm, $minutes);

        return $this->success([
            'vehicle_category_id' => $category->id,
            'vehicle_category_name' => $category->name,
            'distance_km' => $distanceKm,
            'duration_minutes' => $minutes,
            'breakdown' => [
                'base_fare' => number_format($fare['base'], 2, '.', ''),
                'distance_charge' => number_format($fare['distance'], 2, '.', ''),
                'time_charge' => number_format($fare['time'], 2, '.', ''),
                'surge_amount' => number_format($fare['surge_amount'], 2, '.', ''),
                'surge_multiplier' => $fare['surge_multiplier'],
            ],
            'total_fare' => number_format($fare['total'], 2, '.', ''),
            'minimum_fare' => number_format((float) $category->minimum_fare, 2, '.', ''),
            'final_fare' => number_format($fare['total'], 2, '.', ''),
            'surge_active' => $fare['surge_multiplier'] > 1,
            'currency' => $this->settings->get('currency', 'BDT'),
        ], 'Fare estimated.');
    }

    public function nearbyDrivers(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'radius' => ['nullable', 'numeric'],
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
        ]);

        $radius = (float) ($data['radius'] ?? 5);
        $cacheKey = 'nearby_drivers_' . md5(json_encode($data));

        // Cached 10s (rule #2) to spare the DB from frequent map polling.
        $drivers = Cache::remember($cacheKey, 10, function () use ($data, $radius) {
            return Driver::query()->online()->approved()
                ->when(! empty($data['vehicle_category_id']), fn ($q) => $q->whereHas('vehicles', fn ($v) => $v->where('vehicle_category_id', $data['vehicle_category_id'])))
                ->withinRadius((float) $data['lat'], (float) $data['lng'], $radius)
                ->with('activeVehicle.vehicleCategory')
                ->limit(50)
                ->get(['id', 'current_lat', 'current_lng'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'lat' => (float) $d->current_lat,
                    'lng' => (float) $d->current_lng,
                    'vehicle_category' => $d->activeVehicle?->vehicleCategory?->name,
                    'bearing' => 0,
                ])
                ->values();
        });

        return $this->success($drivers, 'Nearby drivers fetched.');
    }

    /// The user's current in-progress order (ride or parcel), if any, so the
    /// app can resume tracking after being killed/restarted.
    public function activeOrder(Request $request)
    {
        $statuses = array_merge(['pending'], \App\Models\Order::ONGOING_STATUSES);
        $order = \App\Models\Order::where('user_id', $request->user()->id)
            ->whereIn('status', $statuses)
            ->latest('id')
            ->first();

        if (! $order) {
            return $this->success(['has_active' => false], 'No active order.');
        }

        $driver = null;
        if ($order->driver) {
            $order->driver->load('activeVehicle.vehicleCategory');
            $v = $order->driver->activeVehicle;
            $driver = [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'phone' => $order->driver->phone,
                'avatar' => $order->driver->avatar ? Storage::url($order->driver->avatar) : null,
                'rating' => (string) $order->driver->average_rating,
                'vehicle' => $v ? [
                    'make' => $v->make,
                    'model' => $v->model,
                    'color' => $v->color,
                    'registration_number' => $v->registration_number,
                    'category' => $v->vehicleCategory?->name,
                ] : null,
                'current_lat' => (float) $order->driver->current_lat,
                'current_lng' => (float) $order->driver->current_lng,
            ];
        }

        return $this->success([
            'has_active' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'status' => $order->status,
            'otp' => $order->otp,
            'payment_method' => $order->payment_method,
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'driver' => $driver,
            'pickup' => [
                'address' => $order->pickup_address,
                'lat' => (float) $order->pickup_lat,
                'lng' => (float) $order->pickup_lng,
            ],
            'drop' => [
                'address' => $order->drop_address,
                'lat' => (float) $order->drop_lat,
                'lng' => (float) $order->drop_lng,
            ],
        ], 'Active order fetched.');
    }
}
