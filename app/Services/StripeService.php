<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Stripe configuration stored in system_settings.
 * The secret key is stored encrypted (rule #3) and only ever surfaced masked.
 */
class StripeService
{
    public function __construct(private SystemSettingService $settings)
    {
    }

    // Decrypt the stored secret key, tolerating legacy plain values.
    public function secretKey(): ?string
    {
        $stored = $this->settings->get('stripe_secret_key');

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return $stored;
        }
    }

    public function publishableKey(): ?string
    {
        return $this->settings->get('stripe_publishable_key');
    }

    // Mask a key for display: keep the prefix and last 4 chars.
    public function mask(?string $key): string
    {
        if (! $key) {
            return '';
        }

        if (strlen($key) <= 12) {
            return str_repeat('•', max(0, strlen($key) - 4)) . substr($key, -4);
        }

        return substr($key, 0, 8) . str_repeat('•', 6) . substr($key, -4);
    }

    // Create a PaymentIntent. Returns ['ok'=>bool, 'id'?, 'client_secret'?, 'message'?].
    public function createPaymentIntent(int $amountMinor, string $currency, array $metadata = []): array
    {
        $key = $this->secretKey();
        if (! $key) {
            return ['ok' => false, 'message' => 'Stripe is not configured.'];
        }

        try {
            $payload = ['amount' => $amountMinor, 'currency' => strtolower($currency), 'automatic_payment_methods[enabled]' => 'true'];
            foreach ($metadata as $k => $v) {
                $payload["metadata[$k]"] = $v;
            }

            $res = Http::withToken($key)->asForm()->timeout(20)->post('https://api.stripe.com/v1/payment_intents', $payload);

            if ($res->successful()) {
                return ['ok' => true, 'id' => $res->json('id'), 'client_secret' => $res->json('client_secret')];
            }

            return ['ok' => false, 'message' => $res->json('error.message') ?? 'Stripe error.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach Stripe: ' . $e->getMessage()];
        }
    }

    // Retrieve a PaymentIntent (to confirm status server-side).
    public function retrievePaymentIntent(string $id): array
    {
        $key = $this->secretKey();
        if (! $key) {
            return ['ok' => false, 'message' => 'Stripe is not configured.'];
        }

        try {
            $res = Http::withToken($key)->timeout(20)->get('https://api.stripe.com/v1/payment_intents/' . $id);
            if ($res->successful()) {
                return ['ok' => true, 'status' => $res->json('status'), 'amount' => $res->json('amount'), 'metadata' => $res->json('metadata')];
            }

            return ['ok' => false, 'message' => $res->json('error.message') ?? 'Stripe error.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach Stripe: ' . $e->getMessage()];
        }
    }

    public function webhookSecret(): ?string
    {
        $stored = $this->settings->get('stripe_webhook_secret');
        if (! $stored) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return $stored;
        }
    }

    // Verify the configured secret key by hitting the Stripe balance endpoint.
    public function testConnection(): array
    {
        $key = $this->secretKey();

        if (! $key) {
            return ['ok' => false, 'message' => 'No Stripe secret key configured.'];
        }

        try {
            $response = Http::withToken($key)
                ->asForm()
                ->timeout(15)
                ->get('https://api.stripe.com/v1/balance');

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Connection successful.'];
            }

            $error = $response->json('error.message') ?? 'Stripe returned HTTP ' . $response->status();

            return ['ok' => false, 'message' => $error];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach Stripe: ' . $e->getMessage()];
        }
    }
}
