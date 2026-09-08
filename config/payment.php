<?php

return [
    // Active gateway used when a caller doesn't specify one.
    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    // Where Stripe (and others) send the user back after checkout. Keep absolute.
    'success_url' => env('PAYMENT_SUCCESS_URL', env('APP_URL') . '/payment/success'),
    'cancel_url' => env('PAYMENT_CANCEL_URL', env('APP_URL') . '/payment/cancel'),

    'gateways' => [
        'stripe' => [
            'driver' => \App\Services\Payment\Gateways\StripeGateway::class,
            'secret_key' => env('STRIPE_SECRET'),
            'currency' => env('STRIPE_CURRENCY', 'usd'),
        ],

        // Future: 'bkash' => [...], 'nagad' => [...]
    ],
];
