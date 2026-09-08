<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Test / Demo Mode
    |--------------------------------------------------------------------------
    |
    | When TRUE the dispatch engine ignores the distance/radius cap AND zone
    | restriction — any available driver receives the order, nearest-first.
    | Use this ONLY for testing/demo so a far-away driver still gets the order.
    |
    | MUST be false (or removed) in production. Set TEST_MODE in your .env, then
    | run `php artisan config:clear` for the change to take effect.
    |
    */
    'test_mode' => env('TEST_MODE', false),

];
