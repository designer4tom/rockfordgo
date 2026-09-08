<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverShiftLog;
use App\Models\Order;
use App\Models\OrderLocation;
use App\Services\DriverLocationService;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Services\ZoneService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DriverOnlineController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
        private ZoneService $zones,
        private DriverLocationService $location,
    ) {
    }

    public function toggleOnline(Request $request)
    {
        // No zone_id — the driver never picks a zone; it's auto-detected from location.
        $data = $request->validate([
            'is_online' => ['required', 'boolean'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $driver = $request->user();

        if ($data['is_online']) {
            // Gate: valid documents + within due limit.
            $reasons = [];
            if ($driver->is_document_expired) {
                $reasons[] = 'One or more documents are expired.';
            }
            if (! $this->wallet->checkDueLimit($driver)) {
                $reasons[] = 'Due amount exceeds the allowed limit.';
            }
            if ($reasons) {
                return $this->error('You cannot go online.', 403, $reasons);
            }

            $lat = $data['lat'] ?? $driver->current_lat;
            $lng = $data['lng'] ?? $driver->current_lng;

            // Auto-detect the zone from the current location (analytics only).
            $zoneId = $this->zones->detectZone(
                $lat !== null ? (float) $lat : null,
                $lng !== null ? (float) $lng : null,
            ) ?? $driver->zone_id;

            $driver->update([
                'is_online' => true,
                'current_lat' => $lat,
                'current_lng' => $lng,
                'last_location_at' => now(),
                'zone_id' => $zoneId,
            ]);

            // Make the driver discoverable for location-based dispatch (Redis GEO).
            $this->location->add($driver->id, $lat !== null ? (float) $lat : null, $lng !== null ? (float) $lng : null);

            // Open a shift log if one isn't already open.
            if (! $driver->shiftLogs()->whereNull('went_offline_at')->exists()) {
                DriverShiftLog::create(['driver_id' => $driver->id, 'went_online_at' => now()]);
            }

            return $this->success(['is_online' => true, 'message' => 'You are now online'], 'Online.');
        }

        $driver->update(['is_online' => false]);

        // Stop offering this driver new orders.
        $this->location->remove($driver->id);

        // Close the open shift log.
        $shift = $driver->shiftLogs()->whereNull('went_offline_at')->latest()->first();
        if ($shift) {
            $shift->update([
                'went_offline_at' => now(),
                'total_minutes' => (int) $shift->went_online_at->diffInMinutes(now()),
            ]);
        }

        return $this->success(['is_online' => false, 'message' => 'You are now offline'], 'Offline.');
    }

    public function updateLocation(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'bearing' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
        ]);

        $driver = $request->user();
        $driver->update(['current_lat' => $data['lat'], 'current_lng' => $data['lng'], 'last_location_at' => now()]);

        // Keep the live GEO position fresh for dispatch (no-op without Redis).
        if ($driver->is_online) {
            $this->location->add($driver->id, (float) $data['lat'], (float) $data['lng']);
        }

        // Refresh the auto-detected zone at most once every 5 min (analytics only).
        $zoneKey = "driver:zone_refresh:{$driver->id}";
        if (! Cache::has($zoneKey)) {
            Cache::put($zoneKey, true, now()->addMinutes(5));
            $zoneId = $this->zones->detectZone((float) $data['lat'], (float) $data['lng']);
            if ($zoneId !== $driver->zone_id) {
                $driver->update(['zone_id' => $zoneId]);
            }
        }

        // If on an active trip, log the breadcrumb and broadcast to the customer.
        $order = $this->activeOrderQuery($driver)->first();
        if ($order) {
            OrderLocation::create([
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'recorded_at' => now(),
            ]);

            // Throttle broadcasts to at most once per 2s per order (Pusher cost control).
            $key = "loc_broadcast:{$order->id}";
            if (! \Illuminate\Support\Facades\Cache::has($key)) {
                broadcast(new \App\Events\DriverLocationUpdatedEvent(
                    $order->id,
                    (float) $data['lat'],
                    (float) $data['lng'],
                    (int) ($data['bearing'] ?? 0),
                ));
                \Illuminate\Support\Facades\Cache::put($key, true, now()->addSeconds(2));
            }
        }

        return $this->success(null, 'Location updated.');
    }

    public function activeOrder(Request $request)
    {
        $driver = $request->user();
        $order = $this->activeOrderQuery($driver)->with('user:id,name,phone')->first();

        if (! $order) {
            return $this->success(null, 'No active order.');
        }

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'type' => $order->type,
            'customer' => ['name' => $order->user->name ?? null, 'phone' => $order->user->phone ?? null],
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
            'otp' => $order->otp,
            'payment_method' => $order->payment_method,
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'is_cod' => (bool) $order->is_cod,
            'cod_amount' => number_format((float) ($order->cod_amount ?? 0), 2, '.', ''),
            // Parcel delivery proof — tells the driver app which proof to collect.
            'proof_required' => $order->type === 'parcel' && \App\Models\SystemSetting::get('proof_of_delivery_enabled', 'true') !== 'false',
            'proof_type' => $order->type === 'parcel' ? ($order->proof_type ?: 'any') : null,
        ], 'Active order fetched.');
    }

    private function activeOrderQuery($driver)
    {
        return Order::where('driver_id', $driver->id)->whereIn('status', Order::ONGOING_STATUSES);
    }
}
