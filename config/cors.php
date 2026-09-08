<?php

return [

    // Apply CORS to the API and Sanctum endpoints (mobile apps).
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Token-based auth (no cookies), so credentials are not required.
    'supports_credentials' => false,

];
