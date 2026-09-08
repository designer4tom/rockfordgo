<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Service;
use App\Services\CouponService;
use App\Services\GeoService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\SystemSettingService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParcelController extends Controller
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

    public function estimate(Request $request)
    {
        $data = $request->validate([
            'parcel_type' => ['required', 'in:normal,fragile,document'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'size' => ['required', 'in:small,medium,large'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'drop_lat' => ['required', 'numeric'],
            'drop_lng' => ['required', 'numeric'],
            'is_cod' => ['nullable', 'boolean'],
        ]);

        $distanceKm = $this->geo->distanceKm($data['pickup_lat'], $data['pickup_lng'], $data['drop_lat'], $data['drop_lng']);
        $charge = $this->pricing->calculateParcelCharge($data['parcel_type'], (float) $data['weight'], $distanceKm);

        if ($charge <= 0) {
            return $this->error('No pricing configured for this parcel type/weight.', 422);
        }

        $base = $charge - round($distanceKm * $this->perKm($data['parcel_type'], (float) $data['weight']), 2);

        $timing = $this->settings->get('parcel_payment_timing', 'both');

        return $this->success([
            'distance_km' => $distanceKm,
            'delivery_charge' => number_format($charge, 2, '.', ''),
            'breakdown' => [
                'base_charge' => number_format(max(0, $base), 2, '.', ''),
                'distance_charge' => number_format($charge - max(0, $base), 2, '.', ''),
            ],
            'payment_timing_options' => $timing === 'both' ? ['before', 'after'] : [$timing],
            'cod_available' => $this->settings->getBool('cod_enabled', true),
            'currency' => $this->settings->get('currency', 'BDT'),
        ], 'Estimate fetched.');
    }

    public function book(Request $request)
    {
        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_phone' => ['required', 'string', 'max:20'],
            'pickup_address' => ['required', 'string'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'drop_address' => ['required', 'string'],
            'drop_lat' => ['required', 'numeric'],
            'drop_lng' => ['required', 'numeric'],
            'parcel_type' => ['required', 'in:normal,fragile,document'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'size' => ['required', 'in:small,medium,large'],
            'parcel_note' => ['nullable', 'string', 'max:500'],
            'parcel_photo' => ['nullable', 'image', 'max:4096'],
            'is_cod' => ['nullable', 'boolean'],
            'cod_amount' => ['nullable', 'numeric', 'min:1', 'required_if:is_cod,1,true'],
            'payment_timing' => ['required', 'in:before,after'],
            'payment_method' => ['required', 'in:cash,online,wallet'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $service = Service::where('type', 'parcel')->where('is_active', true)->first();
        if (! $service) {
            return $this->error('Parcel service is currently unavailable.', 422);
        }

        $distanceKm = $this->geo->distanceKm($data['pickup_lat'], $data['pickup_lng'], $data['drop_lat'], $data['drop_lng']);
        $charge = $this->pricing->calculateParcelCharge($data['parcel_type'], (float) $data['weight'], $distanceKm);
        if ($charge <= 0) {
            return $this->error('No pricing configured for this parcel type/weight.', 422);
        }

        $isCod = ! empty($data['is_cod']);

        // Sender due gate (COD only): block a new COD parcel while the sender's
        // outstanding delivery-charge due is at/over the limit.
        if ($isCod && ! $this->wallet->checkUserDueLimit($user)) {
            return $this->error('Your outstanding delivery-charge due has reached the limit. Please clear it before placing a new COD order.', 403, [
                'due_amount' => number_format((float) $user->due_amount, 2, '.', ''),
                'due_limit' => number_format((float) $this->settings->get('sender_due_limit_amount', 500), 2, '.', ''),
            ]);
        }

        $deliveryTotal = $charge;

        // Coupon (applies to the delivery charge).
        $couponId = null;
        $discount = 0.0;
        if (! empty($data['coupon_code'])) {
            $result = $this->coupons->validate($data['coupon_code'], 'parcel', $deliveryTotal, $user);
            if (! $result['ok']) {
                return $this->error($result['message'], 422);
            }
            $couponId = $result['coupon']->id;
            $discount = $result['discount'];
            $deliveryTotal = $result['final'];
        }

        // Non-COD "before" payment: sender pays the delivery charge now (wallet guard only here).
        if (! $isCod && $data['payment_timing'] === 'before'
            && $data['payment_method'] === 'wallet' && (float) $user->wallet_balance < $deliveryTotal) {
            return $this->error('Insufficient wallet balance. Please top up or choose another payment method.', 400);
        }

        $split = $this->pricing->splitCommission($deliveryTotal, 'parcel');

        $order = DB::transaction(function () use ($data, $user, $service, $distanceKm, $deliveryTotal, $discount, $couponId, $split, $isCod, $request) {
            $order = Order::create([
                'order_number' => 'TMP',
                'user_id' => $user->id,
                'service_id' => $service->id,
                'type' => 'parcel',
                'status' => 'pending',
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'drop_address' => $data['drop_address'],
                'drop_lat' => $data['drop_lat'],
                'drop_lng' => $data['drop_lng'],
                'sender_name' => $data['sender_name'],
                'sender_phone' => $data['sender_phone'],
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'parcel_type' => $data['parcel_type'],
                'parcel_weight' => $data['weight'],
                'parcel_size' => $data['size'],
                'parcel_note' => $data['parcel_note'] ?? null,
                'parcel_photo' => $request->hasFile('parcel_photo') ? $request->file('parcel_photo')->store('parcels', 'public') : null,
                'is_cod' => $isCod,
                'cod_amount' => $isCod ? $data['cod_amount'] : null,
                'distance_km' => $distanceKm,
                'delivery_charge' => $deliveryTotal,
                'coupon_id' => $couponId,
                'coupon_discount' => $discount,
                'total_amount' => $deliveryTotal,
                'admin_commission' => $split['admin_amount'],
                'driver_earning' => $split['driver_amount'],
                'payment_timing' => $data['payment_timing'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'proof_type' => $this->settings->get('proof_type', 'otp'),
            ]);

            $order->update(['order_number' => 'RR-' . now()->year . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);

            if ($couponId) {
                CouponUsage::create(['coupon_id' => $couponId, 'user_id' => $user->id, 'order_id' => $order->id, 'discount_amount' => $discount]);
                \App\Models\Coupon::where('id', $couponId)->increment('used_count');
            }

            return $order;
        });

        $this->notifications->dispatchOrderToDrivers($order);

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_charge' => number_format((float) $order->delivery_charge, 2, '.', ''),
            'cod_amount' => $isCod ? number_format((float) $order->cod_amount, 2, '.', '') : null,
            // COD: the receiver pays only the product price; the delivery charge is
            // billed to the sender's wallet (overflow -> due) at delivery completion.
            'total_receiver_pays' => $isCod ? number_format((float) $order->cod_amount, 2, '.', '') : null,
            'delivery_charge_payer' => $isCod ? 'sender' : null,
            'payment_timing' => $order->payment_timing,
            'payment_method' => $order->payment_method,
            'sender' => ['name' => $order->sender_name, 'phone' => $order->sender_phone],
            'receiver' => ['name' => $order->receiver_name, 'phone' => $order->receiver_phone],
        ], 'Parcel booking confirmed');
    }

    public function status(Request $request, string $orderId)
    {
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        $driver = null;
        if ($order->driver) {
            $driver = [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'phone' => $order->driver->phone,
                'current_lat' => (float) $order->driver->current_lat,
                'current_lng' => (float) $order->driver->current_lng,
                'cod_amount' => $order->is_cod ? number_format((float) $order->cod_amount, 2, '.', '') : null,
                'proof_required' => $this->settings->getBool('proof_of_delivery_enabled', true),
            ];
        }

        return $this->success([
            'status' => $order->status,
            'message' => ucwords(str_replace('_', ' ', $order->status)),
            'driver' => $driver,
            'otp' => $order->otp,
            'payment_method' => $order->payment_method,
            'total_amount' => (string) $order->total_amount,
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
        ], 'Status fetched.');
    }

    public function cancel(Request $request, string $orderId)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if (! in_array($order->status, ['pending', 'accepted', 'go_to_pickup'], true)) {
            return $this->error('This parcel can no longer be cancelled.', 422);
        }

        $refund = 0.0;
        $order->update(['status' => 'cancelled', 'cancelled_by' => 'user', 'cancellation_reason' => $data['reason'], 'cancelled_at' => now()]);

        app(\App\Services\DispatchService::class)->record($order, 'cancelled', $order->driver_id, ['meta' => ['by' => 'user', 'reason' => $data['reason']]]);

        // Before-pay parcel that was already paid → refund the delivery charge.
        if ($order->payment_status === 'paid') {
            $refund = (float) $order->total_amount;
            $this->wallet->refundToCustomer($order->user, $refund, $order->id, 'Parcel cancelled');
        }

        if ($order->driver_id) {
            $this->notifications->sendPush('driver', $order->driver_id, 'Parcel cancelled', 'Customer cancelled ' . $order->order_number . '.', 'order_update');
        }

        return $this->success(['cancellation_fee' => '0.00', 'refund_amount' => number_format($refund, 2, '.', '')], 'Parcel cancelled');
    }

    public function payAfterDelivery(Request $request, string $orderId)
    {
        $data = $request->validate(['payment_method' => ['required', 'in:cash,online,wallet']]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if ($order->payment_status === 'paid') {
            return $this->error('This order is already paid.', 422);
        }

        // Wallet pays the delivery charge now; cash/online are settled out-of-band.
        if ($data['payment_method'] === 'wallet') {
            if ((float) $order->user->wallet_balance < (float) $order->total_amount) {
                return $this->error('Insufficient wallet balance.', 400);
            }
            $this->wallet->debitUser($order->user, (float) $order->total_amount, 'top_up', $order->id, 'Parcel delivery payment ' . $order->order_number);
        }

        $order->update(['payment_method' => $data['payment_method'], 'payment_status' => 'paid']);

        return $this->success(null, 'Payment successful');
    }

    public function rate(Request $request, string $orderId)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
        ]);
        $order = $this->ownedOrder($request, $orderId);
        if (! $order || ! $order->driver_id || $order->status !== 'completed') {
            return $this->error('Cannot rate this order.', 422);
        }

        Rating::updateOrCreate(
            ['order_id' => $order->id, 'rated_by' => 'user', 'ratee_type' => 'driver'],
            ['rater_id' => $order->user_id, 'ratee_id' => $order->driver_id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'tags' => $data['tags'] ?? null]
        );

        $avg = Rating::where('ratee_type', 'driver')->where('ratee_id', $order->driver_id)->avg('rating');
        \App\Models\Driver::where('id', $order->driver_id)->update(['average_rating' => round((float) $avg, 2)]);

        return $this->success(null, 'Rating submitted');
    }

    // Public — receiver tracking by order number + phone.
    public function publicTracking(Request $request, string $orderNumber)
    {
        $request->validate(['phone' => ['required', 'string']]);

        $order = Order::where('order_number', $orderNumber)
            ->where('type', 'parcel')
            ->where('receiver_phone', $request->query('phone'))
            ->with('driver')
            ->first();

        if (! $order) {
            return $this->error('Parcel not found or phone does not match.', 404);
        }

        $eta = null;
        $driver = null;
        if ($order->driver && $order->driver->current_lat) {
            $eta = $this->geo->estimateMinutes($this->geo->distanceKm((float) $order->driver->current_lat, (float) $order->driver->current_lng, (float) $order->drop_lat, (float) $order->drop_lng));
            $driver = ['name' => $order->driver->name, 'current_lat' => (float) $order->driver->current_lat, 'current_lng' => (float) $order->driver->current_lng];
        }

        return $this->success([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => ucwords(str_replace('_', ' ', $order->status)),
            'driver' => $driver,
            'estimated_arrival' => $eta,
            'parcel_type' => $order->parcel_type,
            'cod_amount' => $order->is_cod ? number_format((float) $order->cod_amount, 2, '.', '') : null,
        ], 'Tracking info fetched.');
    }

    // ---------------------------------------------------------------------

    private function ownedOrder(Request $request, string $orderId): ?Order
    {
        return Order::where('id', $orderId)->where('user_id', $request->user()->id)->where('type', 'parcel')->first();
    }

    private function perKm(string $type, float $weight): float
    {
        $pricing = \App\Models\ParcelPricing::where('is_active', true)
            ->where('parcel_type', $type)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->orderBy('base_charge')
            ->first();

        return $pricing ? (float) $pricing->per_km_charge : 0.0;
    }
}
