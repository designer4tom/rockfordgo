<?php

namespace App\Http\Controllers;

use Abedin\MultiPay\Facades\MultiPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Host-side return endpoints for the MultiPay package (joynala/multi-pay).
 *
 * The package owns none of these — it only needs the route NAMES
 * (multipay.success / cancel / failure / callback). Every gateway returns the
 * customer to /payment/{session}/... ; we ALWAYS verify server-side with
 * MultiPay::confirm() before trusting anything, then render the shared
 * payment.result landing page (same as the legacy PaymentCallbackController).
 *
 * Order fulfilment: MultiPay fires Abedin\MultiPay\Events\PaymentSucceeded /
 * PaymentFailed on confirm(); app-side fulfilment lives in a listener so this
 * controller stays about the customer's browser journey only.
 */
class PaymentController extends Controller
{
    // Gateway returned the customer (GET or POST). Verify, then show the result.
    public function success(Request $request, string $session)
    {
        $result = MultiPay::confirm($session, $request);

        if (! $result->success) {
            return $this->page(false, 'Payment Failed', $result->message ?: 'Payment could not be verified.');
        }

        $order = MultiPay::session($session);

        return $this->page(true, 'Payment Successful', 'Your payment is complete.', (float) $order->amount);
    }

    // Server-to-server webhook. Verify (idempotent) and ack with JSON.
    public function callback(Request $request, string $session)
    {
        try {
            MultiPay::confirm($session, $request);
        } catch (\Throwable $e) {
            Log::error('MultiPay callback error', ['session' => $session, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false], 200); // 200 so gateways don't spam retries on our bug
        }

        return response()->json(['ok' => true]);
    }

    // Customer cancelled at the gateway.
    public function cancel(Request $request, string $session)
    {
        MultiPay::cancel($session);

        return $this->page(false, 'Payment Cancelled', 'Your payment was cancelled. No amount was charged.');
    }

    // Gateway reported a failure — confirm() records the proof, then inform the customer.
    public function failure(Request $request, string $session)
    {
        $result = MultiPay::confirm($session, $request);

        return $this->page(false, 'Payment Failed', $result->message ?: 'This payment was not completed.');
    }

    private function page(bool $ok, string $title, string $message, ?float $amount = null, array $rows = [])
    {
        return response()->view('payment.result', [
            'ok' => $ok,
            'title' => $title,
            'message' => $message,
            'amount' => $amount,
            'rows' => $rows,
        ]);
    }
}
