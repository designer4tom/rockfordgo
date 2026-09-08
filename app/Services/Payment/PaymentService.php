<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentRequestData;
use App\Services\Payment\Data\PaymentResponseData;

/**
 * Single entry point for every payment in the app. Resolves the configured
 * gateway (Stripe today; bKash/Nagad later) and delegates to it.
 *
 *   $res = app(PaymentService::class)->pay($request);          // → redirect URL
 *   $res = app(PaymentService::class)->verify($sessionId);     // → isPaid
 */
class PaymentService
{
    public function gateway(?string $name = null): PaymentGateway
    {
        $name = $name ?: config('payment.default', 'stripe');
        $driver = config("payment.gateways.$name.driver");

        if (! $driver || ! class_exists($driver)) {
            throw new \InvalidArgumentException("Unknown payment gateway [$name].");
        }

        return app($driver);
    }

    public function pay(PaymentRequestData $request, ?string $gateway = null): PaymentResponseData
    {
        return $this->gateway($gateway)->requestPayment($request);
    }

    public function verify(string $reference, ?string $gateway = null): PaymentResponseData
    {
        return $this->gateway($gateway)->verifyPayment($reference);
    }
}
