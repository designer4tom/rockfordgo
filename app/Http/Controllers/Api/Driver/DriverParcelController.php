<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\OrderStatusService;
use App\Services\SystemSettingService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DriverParcelController extends Controller
{
    use ApiResponse;

    public function __construct(
        private WalletService $wallet,
        private NotificationService $notifications,
        private SystemSettingService $settings,
        private OrderStatusService $orderStatus,
    ) {
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'status' => ['required', 'in:go_to_pickup,confirm_arrival,picked_up,start_ride,dropped_off'],
        ]);

        $order = $this->driverOrder($request, $data['order_id']);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        // Central status update — sets status + timestamp, broadcasts on order.{id},
        // and notifies the CUSTOMER (Pusher + FCM + in-app) for every parcel status
        // including picked_up (so the customer app can move on / track correctly).
        $this->orderStatus->updateStatus($order, $data['status']);
        $order->refresh();

        $payload = [
            'status' => $data['status'],
            'next_action' => $this->nextAction($data['status']),
            'receiver' => [
                'name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'address' => $order->drop_address,
                'lat' => (float) $order->drop_lat,
                'lng' => (float) $order->drop_lng,
            ],
        ];

        if ($order->is_cod) {
            // Receiver pays the product price only; the delivery charge is billed to
            // the sender's wallet (overflow -> due), so the rider collects cod_amount.
            $payload['cod_reminder'] = [
                'collect_amount' => number_format((float) $order->cod_amount, 2, '.', ''),
                'breakdown' => '৳' . number_format($order->cod_amount, 0) . ' (product price)',
            ];
        }

        return $this->success($payload, 'Status updated');
    }

    public function collectCod(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'collected_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $order = $this->driverOrder($request, $data['order_id']);
        if (! $order || ! $order->is_cod) {
            return $this->error('COD order not found.', 404);
        }

        // The receiver only pays the product price (cod_amount). The delivery charge
        // is billed to the SENDER's wallet at completion (overflow -> sender due), so
        // the rider is expected to collect the product price, not product + delivery.
        $expected = (float) $order->cod_amount;
        if ((float) $data['collected_amount'] + 0.01 < $expected) {
            return $this->error(
                'Collected amount is less than the product price ' . number_format($expected, 2) . '.',
                422,
                [
                    'expected_amount' => number_format($expected, 2, '.', ''),
                    'collected_amount' => number_format((float) $data['collected_amount'], 2, '.', ''),
                    'shortfall' => number_format($expected - (float) $data['collected_amount'], 2, '.', ''),
                    'product_price' => number_format((float) $order->cod_amount, 2, '.', ''),
                    'delivery_charge' => number_format((float) $order->delivery_charge, 2, '.', ''),
                    'delivery_charged_to' => 'sender_wallet',
                ]
            );
        }

        // Remember the collection until delivery is finalised.
        Cache::put("cod_collected_{$order->id}", (float) $data['collected_amount'], now()->addDay());

        $deliveryDriverShare = max(0, (float) $order->delivery_charge - (float) $order->admin_commission);

        return $this->success([
            'collected' => number_format((float) $data['collected_amount'], 2, '.', ''),
            'product_price' => number_format((float) $order->cod_amount, 2, '.', ''),
            'delivery_charge' => number_format((float) $order->delivery_charge, 2, '.', ''),
            'delivery_charged_to' => 'sender_wallet',
            'your_earning' => number_format($deliveryDriverShare, 2, '.', ''),
        ], 'COD collected. Complete delivery to finalize.');
    }

    public function complete(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'proof_type' => ['nullable', 'in:otp,photo,signature'],
            'proof_otp' => ['nullable', 'string'],
            'proof_photo' => ['nullable', 'image', 'max:4096'],
            'proof_signature' => ['nullable', 'string'],
        ]);

        $order = $this->driverOrder($request, $data['order_id']);
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if ($order->status === 'completed') {
            return $this->error('This delivery is already completed.', 422);
        }

        // Proof requirement (rule #2).
        $proofRequired = $this->settings->getBool('proof_of_delivery_enabled', true);
        $proofType = $data['proof_type'] ?? $order->proof_type;
        $proofData = match ($proofType) {
            'photo' => $request->hasFile('proof_photo') ? $request->file('proof_photo')->store('proofs', 'public') : null,
            'signature' => $data['proof_signature'] ?? null,
            default => $data['proof_otp'] ?? null,
        };
        if ($proofRequired && ! $proofData) {
            return $this->error('Proof of delivery is required to complete this order.', 422);
        }

        // COD must be collected first; non-COD after-pay must be settled (rule #3).
        $collected = Cache::get("cod_collected_{$order->id}");
        if ($order->is_cod && $collected === null) {
            return $this->error('Please confirm COD collection before completing the delivery.', 422);
        }
        if (! $order->is_cod && $order->payment_timing === 'after'
            && $order->payment_status !== 'paid' && $order->payment_method !== 'cash') {
            return $this->error('Delivery charge payment must be collected before completing.', 422);
        }

        $driver = $request->user();

        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
            'proof_type' => $proofType,
            'proof_data' => $proofData,
            'proof_collected_at' => now(),
            'payment_status' => 'paid',
        ]);

        // Settlement.
        if ($order->is_cod) {
            $this->wallet->processCodCollection($order->fresh(), (float) $collected);
            Cache::forget("cod_collected_{$order->id}");
            if ($order->user) {
                $this->notifications->sendPush('user', $order->user_id, 'COD received',
                    'আপনার parcel-এর product price wallet-এ যোগ হয়েছে।', 'payment', ['order_id' => (string) $order->id]);
            }
        } elseif ($order->payment_method === 'online') {
            // Online is collected AFTER delivery via MultiPay; the
            // CompleteMultiPayPayment listener (order-{id}) settles the
            // driver's earning once the payment is verified.
        } else {
            $this->wallet->processParcelCommission($order->fresh());
        }

        \App\Models\Driver::where('id', $driver->id)->increment('total_trips');
        broadcast(new \App\Events\OrderCompletedEvent($order->fresh()));

        // Notify the customer the delivery is done (Pusher + FCM + in-app).
        if ($order->user_id) {
            $this->notifications->sendPush('user', $order->user_id, 'Delivery completed',
                'Your parcel has been delivered.', 'order_update',
                ['order_id' => (string) $order->id, 'order_number' => (string) $order->order_number, 'status' => 'completed']);
        }

        $earning = max(0, (float) $order->delivery_charge - (float) $order->admin_commission);

        return $this->success([
            'order_number' => $order->order_number,
            'your_earning' => number_format($earning, 2, '.', ''),
            'wallet_balance' => number_format((float) $driver->fresh()->wallet_balance, 2, '.', ''),
        ], 'Delivery completed!');
    }

    // ---------------------------------------------------------------------

    private function driverOrder(Request $request, $orderId): ?Order
    {
        return Order::where('id', $orderId)->where('driver_id', $request->user()->id)->where('type', 'parcel')->first();
    }

    private function nextAction(string $status): string
    {
        return [
            'go_to_pickup' => 'Head to the sender for pickup',
            'confirm_arrival' => 'Confirm you have arrived at pickup',
            'picked_up' => 'Deliver to receiver',
            'start_ride' => 'On the way to the receiver',
            'dropped_off' => 'Collect COD (if any) and complete delivery',
        ][$status] ?? '';
    }
}
