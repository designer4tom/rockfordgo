<?php

namespace App\Services\Payment\Data;

/**
 * Normalised result returned by every gateway, so callers never touch
 * gateway-specific response shapes.
 */
final class PaymentResponseData
{
    public function __construct(
        public bool $success,
        public string $gateway,
        public ?string $reference,     // gateway txn / session id
        public ?string $redirectUrl,   // checkout URL to open (payment_url)
        public string $status,         // pending | paid | failed | cancelled
        public bool $isPaid,
        public string $message,
        public array $raw = [],        // full gateway payload
    ) {
    }
}
