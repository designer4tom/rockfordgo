<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Service;
use App\Models\VehicleCategory;
use App\Services\CouponService;
use App\Services\GeoService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\SystemSettingService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RideController extends Controller
{
    use ApiResponse;

    public function __construct(
        private GeoService $geo,
        private PricingService $pricing,
        private CouponService $coupons,
        private WalletService $wallet,
        private NotificationService $notifications,
        private SystemSettingService $settings,
    ) {
    }

    public function book(Request $request)
    {
        $data = $request->validate([
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'pickup_address' => ['required', 'string'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'drop_address' => ['required', 'string'],
            'drop_lat' => ['required', 'numeric'],
            'drop_lng' => ['required', 'numeric'],
            'stops' => ['nullable', 'array'],
            'payment_method' => ['required', 'in:cash,online,wallet'],
            'coupon_code' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = $request->user();
        $category = VehicleCategory::findOrFail($data['vehicle_category_id']);
        $service = Service::where('type', 'ride')->where('is_active', true)->first();
        if (! $service) {
            return $this->error('Ride service is currently unavailable.', 422);
        }

        // Distance / duration / fare.
        $points = [[$data['pickup_lat'], $data['pickup_lng']]];
        foreach ($data['stops'] ?? [] as $stop) {
            if (isset($stop['lat'], $stop['lng'])) {
                $points[] = [$stop['lat'], $stop['lng']];
            }
        }
        $points[] = [$data['drop_lat'], $data['drop_lng']];

        $distanceKm = $this->geo->routeDistanceKm($points);
        $minutes = $this->geo->estimateMinutes($distanceKm);
        $fare = $this->pricing->calculateRideFare($category, $distanceKm, $minutes);
        $total = $fare['total'];

        // Coupon.
        $couponId = null;
        $discount = 0.0;
        if (! empty($data['coupon_code'])) {
            $result = $this->coupons->validate($data['coupon_code'], 'ride', $total, $user);
            if (! $result['ok']) {
                return $this->error($result['message'], 422);
            }
            $couponId = $result['coupon']->id;
            $discount = $result['discount'];
            $total = $result['final'];
        }

        // Wallet balance guard (actual debit settles in the payment phase).
        if ($data['payment_method'] === 'wallet' && (float) $user->wallet_balance < $total) {
            return $this->error('Insufficient wallet balance. Please top up or choose another payment method.', 400);
        }

        $split = $this->pricing->splitCommission($total, 'ride');
        $otp = (string) random_int(1000, 9999);

        $order = DB::transaction(function () use ($data, $user, $service, $category, $distanceKm, $minutes, $fare, $total, $discount, $couponId, $split, $otp) {
            $order = Order::create([
                'order_number' => 'TMP',
                'user_id' => $user->id,
                'service_id' => $service->id,
                'vehicle_category_id' => $category->id,
                'type' => 'ride',
                'status' => ! empty($data['scheduled_at']) ? 'scheduled' : 'pending',
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'drop_address' => $data['drop_address'],
                'drop_lat' => $data['drop_lat'],
                'drop_lng' => $data['drop_lng'],
                'stops' => $data['stops'] ?? null,
                'otp' => $otp,
                'distance_km' => $distanceKm,
                'duration_minutes' => $minutes,
                'base_fare' => $fare['base'],
                'distance_charge' => $fare['distance'],
                'time_charge' => $fare['time'],
                'surge_multiplier' => $fare['surge_multiplier'],
                'surge_amount' => $fare['surge_amount'],
                'coupon_id' => $couponId,
                'coupon_discount' => $discount,
                'total_amount' => $total,
                'admin_commission' => $split['admin_amount'],
                'driver_earning' => $split['driver_amount'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            $order->update(['order_number' => 'RR-' . now()->year . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);

            if ($couponId) {
                CouponUsage::create(['coupon_id' => $couponId, 'user_id' => $user->id, 'order_id' => $order->id, 'discount_amount' => $discount]);
                \App\Models\Coupon::where('id', $couponId)->increment('used_count');
            }

            return $order;
        });

        // Kick off the driver-matching loop (skip for scheduled — the scheduler handles those).
        if ($order->status === 'pending') {
            $this->notifications->dispatchOrderToDrivers($order);
        }

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'otp' => $order->otp,
            'fare' => $this->fareBlock($order),
            'payment_method' => $order->payment_method,
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
        ], 'Booking confirmed. Looking for driver...');
    }

    public function status(Request $request, string $orderId)
    {
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        $route = [
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
        ];

        if ($order->status === 'pending' || $order->status === 'scheduled') {
            return $this->success(array_merge([
                'status' => $order->status,
                'message' => 'Looking for driver...',
                'driver' => null,
            ], $route), 'Status fetched.');
        }

        $driver = null;
        if ($order->driver) {
            $order->driver->load('activeVehicle.vehicleCategory');
            $v = $order->driver->activeVehicle;
            $eta = $order->driver->current_lat
                ? $this->geo->estimateMinutes($this->geo->distanceKm((float) $order->driver->current_lat, (float) $order->driver->current_lng, (float) $order->pickup_lat, (float) $order->pickup_lng))
                : null;
            $driver = [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'phone' => $order->driver->phone,
                'avatar' => $order->driver->avatar ? Storage::url($order->driver->avatar) : null,
                'rating' => (string) $order->driver->average_rating,
                'vehicle' => $v ? [
                    'make' => $v->make, 'model' => $v->model, 'color' => $v->color,
                    'registration_number' => $v->registration_number, 'category' => $v->vehicleCategory?->name,
                ] : null,
                'current_lat' => (float) $order->driver->current_lat,
                'current_lng' => (float) $order->driver->current_lng,
                'estimated_arrival' => $eta,
            ];
        }

        return $this->success(array_merge([
            'status' => $order->status,
            'message' => $this->statusMessage($order->status),
            'driver' => $driver,
            'otp' => $order->otp,
            'payment_method' => $order->payment_method,
            'total_amount' => (string) $order->total_amount,
        ], $route), 'Status fetched.');
    }

    public function cancel(Request $request, string $orderId)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        if (! in_array($order->status, ['scheduled', 'pending', 'accepted', 'go_to_pickup'], true)) {
            return $this->error('This ride can no longer be cancelled.', 422);
        }

        // Cancellation fee applies once a driver has accepted (past the grace window).
        $fee = 0.0;
        if ($this->settings->getBool('cancellation_fee_enabled', false) && $order->status !== 'pending' && $order->status !== 'scheduled') {
            $grace = (int) $this->settings->get('cancellation_grace_minutes', 5);
            if ($order->accepted_at && $order->accepted_at->diffInMinutes(now()) > $grace) {
                $fee = (float) $this->settings->get('cancellation_fee_amount', 0);
            }
        }

        $refund = 0.0;
        $order->update([
            'status' => 'cancelled',
            'cancelled_by' => 'user',
            'cancellation_reason' => $data['reason'],
            'cancellation_fee' => $fee,
            'cancelled_at' => now(),
        ]);

        app(\App\Services\DispatchService::class)->record($order, 'cancelled', $order->driver_id, ['meta' => ['by' => 'user', 'reason' => $data['reason']]]);

        if ($order->payment_status === 'paid') {
            $refund = max(0, (float) $order->total_amount - $fee);
            if ($refund > 0) {
                $this->wallet->refundToCustomer($order->user, $refund, $order->id, 'Ride cancelled');
            }
        }

        if ($order->driver_id) {
            $this->notifications->sendPush('driver', $order->driver_id, 'Ride cancelled',
                'Customer cancelled order ' . $order->order_number . '.', 'order_update', ['order_id' => (string) $order->id]);
        }
        broadcast(new \App\Events\OrderCancelledEvent($order->id, 'user', $data['reason']));

        return $this->success([
            'cancellation_fee' => number_format($fee, 2, '.', ''),
            'refund_amount' => number_format($refund, 2, '.', ''),
        ], $fee > 0 ? 'Ride cancelled with cancellation fee' : 'Ride cancelled');
    }

    public function addTip(Request $request, string $orderId)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1']]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if ($order->status !== 'completed') {
            return $this->error('You can only tip after the ride is completed.', 422);
        }

        $order->update(['tip_amount' => (float) $order->tip_amount + (float) $data['amount']]);
        if ($order->driver) {
            $this->wallet->creditDriver($order->driver, (float) $data['amount'], 'trip_earning', $order->id, 'Tip for ' . $order->order_number);
        }

        return $this->success(null, 'Tip added successfully');
    }

    public function rate(Request $request, string $orderId)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
        ]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order || ! $order->driver_id) {
            return $this->error('Order not found.', 404);
        }
        if ($order->status !== 'completed') {
            return $this->error('You can only rate a completed ride.', 422);
        }

        Rating::updateOrCreate(
            ['order_id' => $order->id, 'rated_by' => 'user', 'ratee_type' => 'driver'],
            ['rater_id' => $order->user_id, 'ratee_id' => $order->driver_id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'tags' => $data['tags'] ?? null]
        );

        $this->recalculateDriverRating($order->driver_id);

        return $this->success(null, 'Rating submitted');
    }

    public function generateShareLink(Request $request, string $orderId)
    {
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        $token = (string) Str::uuid();
        $expires = now()->addHours(3);
        $order->update(['share_token' => $token, 'share_token_expires_at' => $expires]);

        return $this->success([
            'share_url' => url('/track/' . $token),
            'expires_at' => $expires->toISOString(),
        ], 'Share link generated.');
    }

    // Public — no auth.
    public function publicTracking(string $token)
    {
        $order = Order::where('share_token', $token)
            ->where('share_token_expires_at', '>', now())
            ->with('driver.activeVehicle')
            ->first();

        if (! $order) {
            return $this->error('This tracking link has expired or is invalid.', 404);
        }

        $driver = null;
        if ($order->driver) {
            $v = $order->driver->activeVehicle;
            $driver = [
                'name' => $order->driver->name,
                'vehicle' => $v ? "{$v->make} {$v->model} ({$v->color}) - {$v->registration_number}" : null,
                'current_lat' => (float) $order->driver->current_lat,
                'current_lng' => (float) $order->driver->current_lng,
            ];
        }

        return $this->success([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'driver' => $driver,
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
        ], 'Tracking info fetched.');
    }

    // ---------------------------------------------------------------------

    private function ownedOrder(Request $request, string $orderId): ?Order
    {
        return Order::where('id', $orderId)->where('user_id', $request->user()->id)->first();
    }

    private function fareBlock(Order $order): array
    {
        return [
            'base_fare' => number_format((float) $order->base_fare, 2, '.', ''),
            'distance_charge' => number_format((float) $order->distance_charge, 2, '.', ''),
            'time_charge' => number_format((float) $order->time_charge, 2, '.', ''),
            'surge_amount' => number_format((float) $order->surge_amount, 2, '.', ''),
            'coupon_discount' => number_format((float) $order->coupon_discount, 2, '.', ''),
            'total' => number_format((float) $order->total_amount, 2, '.', ''),
        ];
    }

    private function statusMessage(string $status): string
    {
        return [
            'accepted' => 'Driver is on the way',
            'go_to_pickup' => 'Driver is heading to pickup',
            'confirm_arrival' => 'Driver has arrived at pickup',
            'picked_up' => 'You have been picked up',
            'start_ride' => 'Ride in progress',
            'dropped_off' => 'Reached destination',
            'completed' => 'Ride completed',
            'cancelled' => 'Ride cancelled',
            'no_driver_found' => 'No driver available. Please try again.',
        ][$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function recalculateDriverRating(int $driverId): void
    {
        $avg = Rating::where('ratee_type', 'driver')->where('ratee_id', $driverId)->avg('rating');
        \App\Models\Driver::where('id', $driverId)->update(['average_rating' => round((float) $avg, 2)]);
    }
}
