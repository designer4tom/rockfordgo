<?php

namespace App\Services\Payment\Data;

/**
 * Gateway-agnostic payment request. Build one of these anywhere a payment is
 * needed (driver recharge, customer top-up, order payment…) and hand it to
 * PaymentService — the gateway turns it into a checkout/redirect link.
 */
final class PaymentRequestData
{
    public function __construct(
        public float $amount,
        public string $reference,          // your own ref (recharge no, order no…)
        public ?string $currency = null,   // null → gateway default
        public array $meta = [],           // title, description, custom keys
        public ?string $customerEmail = null,
        public ?string $successUrl = null, // optional per-request override
        public ?string $cancelUrl = null,
    ) {
    }
}
