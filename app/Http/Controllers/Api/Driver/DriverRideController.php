<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\OrderCompletedEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Services\DispatchService;
use App\Services\NotificationService;
use App\Services\OrderStatusService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DriverRideController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
        private DispatchService $dispatch,
        private OrderStatusService $orderStatus,
    ) {
    }

    public function respond(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'action' => ['required', 'in:accept,reject'],
        ]);

        $driver = $request->user();
        $order = Order::find($data['order_id']);

        if ($data['action'] === 'reject') {
            $this->dispatch->reject($order, $driver);

            return $this->success(null, 'Order rejected.');
        }

        // Accept — race-safe atomic claim + offer-lock check inside the service.
        $result = $this->dispatch->accept($order, $driver);
        if (! $result['ok']) {
            return $this->error($result['message'], 409);
        }

        $order->refresh()->load('user:id,name,phone,avatar');

        $money = fn ($v) => number_format((float) ($v ?? 0), 2, '.', '');
        $total = (float) $order->total_amount;
        $commission = (float) $order->admin_commission;
        $earning = (float) $order->driver_earning;

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer' => [
                'name' => $order->user->name ?? null,
                'phone' => $order->user->phone ?? null,
                'image' => $order->user?->avatar ? asset(\Illuminate\Support\Facades\Storage::url($order->user->avatar)) : null,
            ],
            'pickup' => ['address' => $order->pickup_address, 'lat' => (float) $order->pickup_lat, 'lng' => (float) $order->pickup_lng],
            'drop' => ['address' => $order->drop_address, 'lat' => (float) $order->drop_lat, 'lng' => (float) $order->drop_lng],
            'payment_method' => $order->payment_method,
            'is_cod' => (bool) $order->is_cod,
            'total_amount' => $money($total),
            // Full money breakdown for this trip.
            'fare' => [
                'base_fare' => $money($order->base_fare),
                'surge_amount' => $money($order->surge_amount),
                'coupon_discount' => $money($order->coupon_discount),
                'tip_amount' => $money($order->tip_amount),
                // Total fare the customer is charged for the trip.
                'total_fare' => $money($total),
                // Cash the driver collects in hand: COD parcel = product price; prepaid online/wallet = 0; else the trip total.
                'customer_payable' => $money($order->is_cod ? $order->cod_amount : (in_array($order->payment_method, ['online', 'wallet'], true) ? 0 : $total)),
                // Platform cut and the driver's net earning from the fare.
                'admin_commission' => $money($commission),
                'commission_percent' => $total > 0 ? round($commission / $total * 100, 2) : 0,
                'driver_earning' => $money($earning),
            ],
        ], 'Order accepted');
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'status' => ['required', 'in:go_to_pickup,confirm_arrival,picked_up,start_ride,dropped_off,completed'],
            'otp' => ['nullable', 'string'],
        ]);

        $driver = $request->user();
        $order = Order::where('id', $data['order_id'])->where('driver_id', $driver->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        // OTP gate for pickup.
        if ($data['status'] === 'picked_up' && (string) ($data['otp'] ?? '') !== (string) $order->otp) {
            return $this->error('Invalid OTP. Please ask the customer for the correct OTP.', 422);
        }

        // Central status update — sets status + timestamp, broadcasts, notifies.
        $this->orderStatus->updateStatus($order, $data['status']);

        // On completion settle commission + payment + the completed real-time event.
        if ($data['status'] === 'completed') {
            if ($order->payment_method === 'online') {
                // Online is collected AFTER the trip via MultiPay: the customer
                // picks a gateway and pays; CompleteMultiPayPayment (order-{id})
                // marks the order paid and credits the driver. Nothing settles here.
            } else {
                $this->wallet->processTripCommission($order->fresh());
                if ($order->payment_method === 'wallet') {
                    $order->update(['payment_status' => 'paid']);
                }
            }
            \App\Models\Driver::where('id', $driver->id)->increment('total_trips');
            broadcast(new OrderCompletedEvent($order->fresh()));
        }

        return $this->success([
            'order_id' => $order->id,
            'status' => $data['status'],
            'next_action' => $this->orderStatus->nextAction($data['status']),
        ], 'Status updated');
    }

    // POST /driver/ride/{orderId}/cancel — allowed only BEFORE pickup. The
    // order is re-dispatched to other drivers, never silently killed.
    public function cancel(Request $request, int $orderId)
    {
        $driver = $request->user();
        $order = Order::where('id', $orderId)->where('driver_id', $driver->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if (! in_array($order->status, ['accepted', 'go_to_pickup', 'confirm_arrival'], true)) {
            return $this->error('You can only cancel before the trip starts.', 422);
        }

        $this->dispatch->driverCancel($order, $driver);

        return $this->success(['order_id' => $order->id], 'Ride cancelled. The order is being reassigned.');
    }

    public function collectProof(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'proof_type' => ['required', 'in:otp,photo,signature'],
            'otp' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'signature' => ['nullable', 'string'],
        ]);

        $driver = $request->user();
        $order = Order::where('id', $data['order_id'])->where('driver_id', $driver->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        $proofData = match ($data['proof_type']) {
            'otp' => $data['otp'] ?? null,
            'photo' => $request->hasFile('photo') ? $request->file('photo')->store('proofs', 'public') : null,
            'signature' => $data['signature'] ?? null,
        };

        $order->update([
            'proof_type' => $data['proof_type'],
            'proof_data' => $proofData,
            'proof_collected_at' => now(),
        ]);

        return $this->success(null, 'Proof collected successfully');
    }

    public function rate(Request $request, string $orderId)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
        ]);

        $driver = $request->user();
        $order = Order::where('id', $orderId)->where('driver_id', $driver->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        Rating::updateOrCreate(
            ['order_id' => $order->id, 'rated_by' => 'driver', 'ratee_type' => 'user'],
            ['rater_id' => $driver->id, 'ratee_id' => $order->user_id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'tags' => $data['tags'] ?? null]
        );

        return $this->success(null, 'Rating submitted');
    }

    private function nextAction(string $status): string
    {
        return [
            'go_to_pickup' => "Press 'Arrived' when you reach the pickup point",
            'confirm_arrival' => 'Ask the customer for the OTP to start the trip',
            'picked_up' => "Press 'Start' to begin the ride",
            'start_ride' => "Press 'Reached' at the destination",
            'dropped_off' => "Press 'Complete' to finish the trip",
            'completed' => 'Trip completed',
        ][$status] ?? '';
    }
}
