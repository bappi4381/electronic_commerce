<?php

/*
|--------------------------------------------------------------------------
| Fraud protection
|--------------------------------------------------------------------------
|
| Defaults only: every value here can be changed in Admin › Settings › Fraud,
| which takes precedence. API keys entered there are stored encrypted.
|
*/

return [

    // Same phone + same products within this many minutes is treated as a duplicate (0 = off).
    'duplicate_minutes' => (int) env('FRAUD_DUPLICATE_MINUTES', 30),

    // Orders allowed from one IP address per hour (0 = off); "flag" places the order and marks it, "block" refuses it.
    'ip_max_per_hour' => (int) env('FRAUD_IP_MAX_PER_HOUR', 5),
    'ip_action' => env('FRAUD_IP_ACTION', 'flag'),

    // Delivery-history check after each order.
    'enabled' => (bool) env('FRAUD_CHECK_ENABLED', true),
    'driver' => env('FRAUD_CHECKER_DRIVER', 'local'),
    'cache_hours' => 24,
    'timeout' => 15,

    // Risk levels: success rate >= low_from is Low, >= medium_from is Medium, below is High.
    'low_from' => 80,
    'medium_from' => 50,
    // This many cancelled/returned parcels make a customer High risk regardless of the rate.
    'high_cancel_count' => (int) env('FRAUD_HIGH_CANCEL_COUNT', 3),

    // What happens to High-risk cash-on-delivery orders.
    'hold_high_risk' => (bool) env('FRAUD_HOLD_HIGH_RISK', true),
    'advance_enabled' => (bool) env('FRAUD_ADVANCE_ENABLED', false),
    'advance_type' => env('FRAUD_ADVANCE_TYPE', 'delivery_charge'), // delivery_charge | fixed
    'advance_amount' => (float) env('FRAUD_ADVANCE_AMOUNT', 0),

    'drivers' => [
        // This store's own orders: delivered vs returned/refused for the number. No API needed.
        'local' => [],

        // Steadfast merchant fraud check; uses the Steadfast API keys from Settings › Courier.
        'steadfast' => [
            'path' => env('FRAUD_STEADFAST_PATH', '/fraud_check/{phone}'),
        ],

        // BD Courier (bdcourier.com): combined history across Pathao, Steadfast, RedX, Paperfly…
        'bdcourier' => [
            'url' => env('FRAUD_BDCOURIER_URL', 'https://api.bdcourier.com/courier-check'),
            'api_key' => env('FRAUD_BDCOURIER_API_KEY'),
        ],
    ],

];
