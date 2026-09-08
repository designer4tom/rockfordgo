<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\Data\PaymentRequestData;
use App\Services\Payment\Data\PaymentResponseData;

interface PaymentGateway
{
    public function name(): string;

    // Create a payment and return a redirect/checkout link.
    public function requestPayment(PaymentRequestData $payment): PaymentResponseData;

    // Verify a payment server-side (after redirect / webhook) by its reference.
    public function verifyPayment(string $reference): PaymentResponseData;
}
