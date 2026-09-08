<?php

namespace App\Http\Controllers\Api\Customer;

use Abedin\MultiPay\Facades\MultiPay;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Post-trip online payment via MultiPay.
 *
 * Flow: the trip completes with payment_method=online and payment_status
 * still `pending`; the app shows the active gateways (config
 * `payment_methods`), the customer picks one, we hand back a hosted checkout
 * URL. The return routes call MultiPay::confirm(), which fires
 * PaymentSucceeded → CompleteMultiPayPayment (order_id "order-{id}") marks
 * the order paid and settles the driver's earning (total − admin commission).
 */
class OrderPaymentController extends Controller
{
    use ApiResponse;

    // POST /user/orders/{orderId}/pay-online  {gateway}
    public function initiate(Request $request, int $orderId)
    {
        $data = $request->validate([
            // gateway IS the MultiPay gateway key (stripe, razorpay, …).
            'gateway' => ['required', Rule::in(array_column(MultiPay::activeGateways(), 'name'))],
        ]);

        $user = $request->user();
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }
        if ($order->payment_method !== 'online') {
            return $this->error('This order is not payable online.', 422);
        }
        if ($order->payment_status === 'paid') {
            return $this->error('This order is already paid.', 422);
        }
        if (! in_array($order->status, ['completed'], true)) {
            return $this->error('Payment opens after the trip completes.', 422);
        }

        try {
            $res = MultiPay::gateway($data['gateway'])->pay([
                'amount' => (float) $order->total_amount,
                'currency' => SystemSetting::get('currency', 'BDT'),
                'order_id' => 'order-' . $order->id,
                'customer' => ['name' => $user->name, 'email' => $user->email],
                'meta' => [
                    'title' => 'Trip ' . $order->order_number,
                    'description' => ucfirst($order->type) . ' payment',
                    'phone' => $user->phone,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error('Payment could not be started: ' . $e->getMessage(), 422);
        }

        if (empty($res['success'])) {
            return $this->error($res['message'] ?? 'Payment could not be started.', 422);
        }

        return $this->success([
            'payment_url' => $res['payment_url'] ?? $res['url'] ?? null,
            'gateway' => $data['gateway'],
            'amount' => (string) $order->total_amount,
        ], 'Payment session created.');
    }

    // GET /user/orders/{orderId}/payment-status — the app polls this after the
    // hosted checkout returns; only the server-verified status is trusted.
    public function status(Request $request, int $orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->first();
        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success([
            'payment_status' => $order->payment_status,
            'paid' => $order->payment_status === 'paid',
        ]);
    }
}
