<?php

namespace App\Services;

use App\Events\OrderAcceptedEvent;
use App\Jobs\CheckOrderResponseJob;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderDispatchLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Event-driven order dispatch — NO sleep()/while-blocking.
 *
 * Flow: start() picks the nearest eligible driver and offers it (Pusher + FCM),
 * then schedules a delayed CheckOrderResponseJob. Accept/reject/timeout each
 * trigger offerToNextDriver() — one quick step per event. State + the per-driver
 * offer "lock" live in the cache (works on the current DB cache; Redis-ready).
 */
class DispatchService
{
    /**
     * Test-mode radius — effectively "no cap". Large enough to cover any real
     * service area while still letting Redis GEO / SQL Haversine sort nearest-first.
     */
    private const UNLIMITED_RADIUS_KM = 100000.0;

    public function __construct(
        private NotificationService $notifications,
        private OrderStatusService $status,
        private DriverLocationService $location,
        private ZoneService $zones,
    ) {
    }

    /**
     * Test/demo mode — when ON, dispatch ignores BOTH the distance/radius cap and
     * the zone restriction. Controlled from .env (TEST_MODE) via config/readyride.php
     * — run `php artisan config:clear` after changing it. MUST be false in production.
     */
    private function testMode(): bool
    {
        return (bool) config('readyride.test_mode', false);
    }

    private function stateKey(int $orderId): string
    {
        return "order:dispatch:{$orderId}";
    }

    private function offerKey(int $orderId, int $driverId): string
    {
        return "order:offer:{$orderId}:{$driverId}";
    }

    public function timeoutSeconds(): int
    {
        return (int) SystemSetting::get('request_timeout_seconds', 30);
    }

    /** Begin dispatching a freshly-booked order. */
    public function start(Order $order): void
    {
        if ($this->testMode()) {
            Log::channel('dispatch')->info("Dispatch in TEST MODE — radius/zone ignored for order #{$order->order_number}", ['order_id' => $order->id]);
        }

        Cache::put($this->stateKey($order->id), [
            'tried' => [],
            'current' => null,
            'attempt' => 0,
            'radius' => (float) SystemSetting::get('search_radius_km', 5),
        ], now()->addHours(2));

        $this->offerToNextDriver($order->id);
    }

    /** Offer the order to the next nearest eligible driver, or give up. */
    public function offerToNextDriver(int $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order || ! in_array($order->status, ['pending', 'scheduled'], true)) {
            return; // already accepted / cancelled / resolved
        }

        $state = Cache::get($this->stateKey($orderId)) ?? ['tried' => [], 'current' => null, 'attempt' => 0, 'radius' => (float) SystemSetting::get('search_radius_km', 5)];
        $maxAttempts = (int) SystemSetting::get('max_dispatch_attempts', 10);

        if (count($state['tried']) >= $maxAttempts) {
            $this->noDriverFound($order, $state);
            return;
        }

        $driver = $this->findDriver($order, $state);
        if (! $driver) {
            $this->noDriverFound($order, $state);
            return;
        }

        // Record the offer + arm the per-driver lock for the decision window.
        $state['current'] = $driver->id;
        $state['tried'][] = $driver->id;
        $state['attempt']++;
        Cache::put($this->stateKey($orderId), $state, now()->addHours(2));

        $timeout = $this->timeoutSeconds();
        Cache::put($this->offerKey($orderId, $driver->id), true, now()->addSeconds($timeout + 5));

        // Audit trail — who got offered, on which attempt, at what radius.
        $this->record($order, 'offered', $driver->id, [
            'attempt' => $state['attempt'],
            'radius_km' => $state['radius'] ?? null,
            'meta' => ['timeout_seconds' => $timeout, 'driver_name' => $driver->name, 'driver_phone' => $driver->phone],
        ]);

        // Pusher (instant) + FCM (backup) — both inside sendOrderRequest.
        $this->notifications->sendOrderRequest($driver, $order);

        // Quick delayed job — fires after the decision window if no response.
        CheckOrderResponseJob::dispatch($orderId, $driver->id)
            ->onQueue('dispatch')
            ->delay(now()->addSeconds($timeout + 1));
    }

    /** Driver accepts. Race-safe via an atomic conditional update. */
    public function accept(Order $order, Driver $driver): array
    {
        // The offer must still be live for this driver (not timed out / moved on).
        if (! Cache::has($this->offerKey($order->id, $driver->id))) {
            return ['ok' => false, 'message' => 'This order is no longer available.'];
        }

        // Atomic claim — only one driver can flip an unassigned pending order.
        $claimed = Order::where('id', $order->id)
            ->whereNull('driver_id')
            ->whereIn('status', ['pending', 'scheduled'])
            ->update([
                'driver_id' => $driver->id,
                'status' => 'accepted',
                'accepted_at' => now(),
                'driver_assigned_at' => now(),
            ]);

        if ($claimed !== 1) {
            return ['ok' => false, 'message' => 'This order has already been taken by another driver.'];
        }

        $this->record($order, 'accepted', $driver->id, ['meta' => ['driver_name' => $driver->name, 'driver_phone' => $driver->phone]]);
        $this->clearDispatch($order->id);
        $order->refresh();

        // Open the ride/parcel chat. Done explicitly here because the claim above
        // is a query-builder mass update (deliberately — it is the atomic guard
        // against two drivers taking the same order), and those do NOT fire
        // Eloquent model events, so OrderObserver never sees this transition.
        try {
            app(ChatService::class)->openForOrder($order);
        } catch (\Throwable $e) {
            Log::error('Chat open on accept failed: ' . $e->getMessage());
        }

        broadcast(new OrderAcceptedEvent($order, $driver->loadMissing('activeVehicle')));
        if ($order->user_id) {
            // order_accepted rule event (push + SMS per Notification Rules).
            $this->notifications->notifyEvent('order_accepted', 'user', $order->user_id, 'Driver assigned',
                'A driver has accepted your request and is on the way.', 'order_update',
                ['order_id' => (string) $order->id, 'order_number' => (string) $order->order_number, 'status' => 'accepted'],
                User::whereKey($order->user_id)->value('phone'));
        }

        return ['ok' => true];
    }

    /** Driver rejects → immediately offer to the next driver. */
    /**
     * Driver backs out AFTER accepting but BEFORE pickup: unassign the order,
     * tell the customer, and restart dispatch with the cancelling driver
     * excluded so they are never re-offered the trip they walked away from.
     */
    public function driverCancel(Order $order, Driver $driver): void
    {
        $this->record($order, 'driver_cancelled', $driver->id, ['meta' => ['driver_name' => $driver->name]]);

        $order->update([
            'driver_id' => null,
            'status' => 'pending',
            'accepted_at' => null,
            'driver_assigned_at' => null,
        ]);

        // Fresh dispatch state pre-seeded with the canceller in `tried`.
        Cache::put($this->stateKey($order->id), [
            'tried' => [$driver->id],
            'current' => null,
            'attempt' => 0,
            'radius' => (float) SystemSetting::get('search_radius_km', 5),
        ], now()->addHours(2));

        if ($order->user_id) {
            $this->notifications->sendPush('user', $order->user_id, 'Finding a new driver',
                'Your driver had to cancel. We are searching for a new driver for you.', 'order_update',
                ['order_id' => (string) $order->id, 'order_number' => (string) $order->order_number, 'status' => 'searching']);
        }
        // Realtime nudge on the order channel; the app's 5s polling fallback
        // also picks the reset up even if the socket is down.
        $this->notifications->sendPusher('private-order.' . $order->id, 'OrderStatusUpdated', [
            'order_id' => $order->id,
            'status' => 'pending',
            'driver_cancelled' => true,
        ]);

        $this->offerToNextDriver($order->id);
    }

    public function reject(Order $order, Driver $driver): void
    {
        Cache::forget($this->offerKey($order->id, $driver->id));
        $this->record($order, 'rejected', $driver->id, ['meta' => ['driver_name' => $driver->name]]);

        $state = Cache::get($this->stateKey($order->id));
        if ($state) {
            if (! in_array($driver->id, $state['tried'], true)) {
                $state['tried'][] = $driver->id;
            }
            $state['current'] = null;
            Cache::put($this->stateKey($order->id), $state, now()->addHours(2));
        }

        $this->offerToNextDriver($order->id);
    }

    /** Called by the delayed job when a driver didn't respond in time. */
    public function handleTimeout(int $orderId, int $driverId): void
    {
        $order = Order::find($orderId);
        if (! $order || ! in_array($order->status, ['pending', 'scheduled'], true)) {
            return; // already accepted / cancelled
        }

        $state = Cache::get($this->stateKey($orderId));
        // Only advance if this driver is still the active offer (didn't reject/accept).
        if (! $state || ($state['current'] ?? null) !== $driverId) {
            return;
        }

        Cache::forget($this->offerKey($orderId, $driverId));
        $state['current'] = null;
        Cache::put($this->stateKey($orderId), $state, now()->addHours(2));

        $this->record($order, 'timeout', $driverId);

        $this->offerToNextDriver($orderId);
    }

    // ---------------------------------------------------------------------

    // Nearest eligible driver, expanding the radius up to the max (quick DB
    // queries — no blocking). Location-based; zone is only a filter in strict mode.
    private function findDriver(Order $order, array &$state): ?Driver
    {
        // Test/demo mode: ignore the radius cap entirely — any available driver,
        // still nearest-first. Solves "no driver found" when the only drivers are
        // far away (client demos, sparse test data).
        if ($this->testMode()) {
            $state['radius'] = self::UNLIMITED_RADIUS_KM;

            return $this->nearestEligible($order, $state['tried'], self::UNLIMITED_RADIUS_KM);
        }

        $radius = (float) ($state['radius'] ?? SystemSetting::get('search_radius_km', 5));
        $max = (float) SystemSetting::get('max_radius_km', 10);
        $inc = max(1.0, (float) SystemSetting::get('radius_expand_km', 1));

        $driver = null;
        while (! $driver && $radius <= $max) {
            $driver = $this->nearestEligible($order, $state['tried'], $radius);
            if (! $driver) {
                $radius += $inc;
            }
        }

        $state['radius'] = min($radius, $max);

        return $driver;
    }

    // Closest eligible driver within $radius. Prefers Redis GEO (GEOSEARCH) when
    // it has live data, but ALWAYS falls back to the SQL Haversine query when GEO
    // is unavailable OR returns no eligible driver — so a stale/empty GEO set can
    // never make dispatch miss drivers that genuinely exist in the DB.
    private function nearestEligible(Order $order, array $tried, float $radius): ?Driver
    {
        $lat = (float) $order->pickup_lat;
        $lng = (float) $order->pickup_lng;

        $geoIds = $this->location->nearby($lat, $lng, $radius);

        if (! empty($geoIds)) {
            // Redis GEO path — keep nearest-first order, drop already-tried IDs.
            $candidates = array_values(array_filter(array_diff($geoIds, $tried ?: [])));

            if ($candidates) {
                $eligible = $this->eligibleDrivers($order, $tried)
                    ->whereIn('id', $candidates)
                    ->get()
                    ->keyBy('id');

                foreach ($candidates as $id) {
                    if ($eligible->has($id)) {
                        return $eligible->get($id);
                    }
                }
            }
            // GEO returned nothing usable → fall through to SQL (don't trust a stale GEO set).
        }

        // SQL Haversine — works with no Redis, and rescues a stale/empty GEO set.
        return $this->eligibleDrivers($order, $tried)
            ->withinRadius($lat, $lng, $radius)
            ->orderByDistance($lat, $lng)
            ->first();
    }

    // The eligibility filter: who is allowed to receive this order.
    private function eligibleDrivers(Order $order, array $tried)
    {
        // A driver "online" but not pinging location recently is a stale/ghost
        // session (e.g. crashed app, duplicate account) — skip it so it doesn't
        // eat a dispatch slot and time out before a real driver is reached.
        $staleMinutes = (int) SystemSetting::get('driver_online_timeout_minutes', 10);

        // Zone mode (default 'flexible'): in 'strict' mode a driver only gets orders
        // whose pickup falls in the driver's own zone. We detect the order's zone
        // from its pickup location; if no zone is detectable we don't restrict
        // (can't strict-filter on an unknown zone) — i.e. behave like flexible.
        // Test mode forces zone soft regardless of the admin setting.
        $strictZoneId = null;
        if (! $this->testMode() && SystemSetting::get('zone_mode', 'flexible') === 'strict') {
            $strictZoneId = $this->zones->detectZone((float) $order->pickup_lat, (float) $order->pickup_lng);
        }

        return Driver::query()
            ->online()
            ->approved()
            ->where('can_accept_orders', true)          // encodes due-limit (set by WalletService)
            ->where('last_location_at', '>=', now()->subMinutes($staleMinutes))
            ->whereNotIn('id', $tried ?: [0])
            ->when($strictZoneId, fn ($q) => $q->where('zone_id', $strictZoneId))
            // Free — no order currently in progress.
            ->whereDoesntHave('orders', fn ($q) => $q->whereIn('status', Order::ONGOING_STATUSES))
            // Ride: vehicle category must match the requested one.
            ->when($order->type === 'ride' && $order->vehicle_category_id, fn ($q) => $q->whereHas('vehicles', fn ($v) => $v->where('vehicle_category_id', $order->vehicle_category_id)->where('is_active', true)))
            // COD parcel: driver must be able to float the product price.
            ->when($order->is_cod && $order->cod_amount, fn ($q) => $q->where('wallet_balance', '>=', $order->cod_amount));
    }

    private function noDriverFound(Order $order, array $state): void
    {
        $this->record($order, 'no_driver_found', null, [
            'attempt' => $state['attempt'] ?? 0,
            'radius_km' => $state['radius'] ?? null,
            'meta' => ['tried_drivers' => $state['tried'] ?? []],
        ]);
        $this->clearDispatch($order->id, $state);
        $this->status->updateStatus($order, 'no_driver_found');
    }

    // Persist one dispatch event (DB audit row + real-time 'dispatch' log line).
    public function record(Order $order, string $event, ?int $driverId = null, array $extra = []): void
    {
        OrderDispatchLog::create([
            'order_id' => $order->id,
            'driver_id' => $driverId,
            'event' => $event,
            'attempt' => $extra['attempt'] ?? 0,
            'radius_km' => $extra['radius_km'] ?? null,
            'meta' => $extra['meta'] ?? null,
        ]);

        Log::channel('dispatch')->info("order #{$order->order_number} [{$event}]", array_filter([
            'order_id' => $order->id,
            'driver_id' => $driverId,
            'attempt' => $extra['attempt'] ?? null,
            'radius_km' => $extra['radius_km'] ?? null,
        ], fn ($v) => $v !== null));
    }

    private function clearDispatch(int $orderId, ?array $state = null): void
    {
        $state = $state ?? Cache::get($this->stateKey($orderId));
        if ($state) {
            foreach (($state['tried'] ?? []) as $driverId) {
                Cache::forget($this->offerKey($orderId, $driverId));
            }
        }
        Cache::forget($this->stateKey($orderId));
    }
}
