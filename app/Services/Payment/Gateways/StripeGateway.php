<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentRequestData;
use App\Services\Payment\Data\PaymentResponseData;
use Illuminate\Support\Facades\Http;

/**
 * Stripe Checkout Session gateway. Uses the Stripe REST API directly (no SDK
 * dependency, consistent with the rest of the app). Returns a hosted checkout
 * URL the client opens; verifyPayment() confirms payment server-side.
 *
 * Keys are read from config/payment.php (→ .env STRIPE_SECRET).
 */
class StripeGateway implements PaymentGateway
{
    private const BASE = 'https://api.stripe.com/v1';

    private array $config;

    public function __construct()
    {
        $this->config = config('payment.gateways.stripe', []);
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function requestPayment(PaymentRequestData $payment): PaymentResponseData
    {
        $key = $this->config['secret_key'] ?? null;
        if (! $key) {
            return $this->fail('Stripe is not configured (missing secret key).');
        }

        $currency = strtolower($payment->currency ?: ($this->config['currency'] ?? 'usd'));
        $success = ($payment->successUrl ?: config('payment.success_url'));
        $success .= (str_contains($success, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id' => $payment->reference,
            'success_url' => $success,
            'cancel_url' => $payment->cancelUrl ?: config('payment.cancel_url'),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round($payment->amount * 100),
                    'product_data' => [
                        'name' => $payment->meta['title'] ?? ('Payment ' . $payment->reference),
                        'description' => $payment->meta['description'] ?? ('Payment for ' . $payment->reference),
                    ],
                ],
            ]],
            'metadata' => array_merge(
                ['reference' => $payment->reference],
                array_filter($payment->meta, 'is_scalar')
            ),
        ];

        if ($payment->customerEmail) {
            $payload['customer_email'] = $payment->customerEmail;
        }

        try {
            $res = Http::withToken($key)->asForm()->timeout(20)->post(self::BASE . '/checkout/sessions', $payload);

            if ($res->successful()) {
                return new PaymentResponseData(
                    success: true,
                    gateway: $this->name(),
                    reference: $res->json('id'),
                    redirectUrl: $res->json('url'),
                    status: 'pending',
                    isPaid: false,
                    message: 'Stripe checkout session created.',
                    raw: $res->json(),
                );
            }

            return $this->fail($res->json('error.message') ?? 'Stripe error.', $res->json());
        } catch (\Throwable $e) {
            return $this->fail('Could not reach Stripe: ' . $e->getMessage());
        }
    }

    public function verifyPayment(string $reference): PaymentResponseData
    {
        $key = $this->config['secret_key'] ?? null;
        if (! $key) {
            return $this->fail('Stripe is not configured (missing secret key).');
        }

        try {
            $res = Http::withToken($key)->timeout(20)->get(self::BASE . '/checkout/sessions/' . $reference);

            if (! $res->successful()) {
                return $this->fail($res->json('error.message') ?? 'Stripe error.', $res->json());
            }

            $paid = $res->json('payment_status') === 'paid';

            return new PaymentResponseData(
                success: true,
                gateway: $this->name(),
                reference: $res->json('id'),
                redirectUrl: $res->json('url'),
                status: $paid ? 'paid' : ($res->json('status') ?? 'pending'),
                isPaid: $paid,
                message: $paid ? 'Payment confirmed.' : 'Payment not completed yet.',
                raw: $res->json(),
            );
        } catch (\Throwable $e) {
            return $this->fail('Could not reach Stripe: ' . $e->getMessage());
        }
    }

    private function fail(string $message, array $raw = []): PaymentResponseData
    {
        return new PaymentResponseData(false, $this->name(), null, null, 'failed', false, $message, $raw);
    }
}
