<?php

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
|
| Values the admin "Settings" screen does not manage. Where a Setting with the
| same meaning exists (e.g. free_shipping_threshold) the Setting wins and the
| value here is only the fallback.
|
*/

return [
    'currency' => [
        'code' => 'BDT',
        'symbol' => '৳',
        // Render prices with Bangla digits (১,২৩৪) on /bn pages.
        'bangla_digits' => (bool) env('STORE_BANGLA_DIGITS', false),
    ],

    // Free delivery on orders at or above this amount (BDT). null/0 disables it.
    'free_shipping_threshold' => env('STORE_FREE_SHIPPING_THRESHOLD', 3000),

    'return_days' => (int) env('STORE_RETURN_DAYS', 30),

    // Times the admin types without a zone (e.g. the flash deal end) are read in this timezone.
    'timezone' => env('STORE_TIMEZONE', 'Asia/Dhaka'),

    // Flash sale: how many flagged products the campaign shows, and how long the product list is cached (seconds).
    'flash_sale_limit' => 12,
    'flash_sale_cache_seconds' => 60,

    'hotline' => env('STORE_HOTLINE'),

    // Shown in the desktop brand strip when those brands exist in the catalog (by name, case-insensitive).
    'featured_brands' => ['Walton', 'Vision', 'Samsung', 'Apple', 'Philips', 'Sharp', 'Miyako', 'Xiaomi', 'Realme', 'JBL', 'Anker', 'HP'],

    // Bootstrap icon per top-level category name when the category has none.
    'category_icons' => [
        'electronics' => 'bi-cpu',
        'computers' => 'bi-laptop',
        'mobile' => 'bi-phone',
        'phones' => 'bi-phone',
        'home & kitchen' => 'bi-cup-hot',
        'home appliances' => 'bi-plug',
        'beauty' => 'bi-droplet',
        'books & stationery' => 'bi-journal-bookmark',
        'sports' => 'bi-trophy',
        'fashion' => 'bi-handbag',
        'clothing' => 'bi-handbag',
        'footwear' => 'bi-bag',
    ],
];
