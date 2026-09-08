<?php

return [
    // Fallback minimum recharge/top-up amount. Overridden by the
    // `min_recharge_amount` system setting when present.
    // Payment methods & gateways are managed entirely by MultiPay
    // (config/multipay.php + the gateways table via Admin → Payment Gateways).
    'min_amount' => 50,
];
