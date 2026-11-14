<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        // Facebook Login (Socialite)
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),

        // Meta Pixel (browser) + Conversions API (server). Both can be switched off in Admin › Settings › Tracking.
        'pixel_id' => env('FB_PIXEL_ID'),
        'capi_access_token' => env('FB_CAPI_ACCESS_TOKEN'),
        // Set while testing in Events Manager › Test events; leave empty in production.
        'test_event_code' => env('FB_TEST_EVENT_CODE'),
        'graph_version' => env('FB_GRAPH_VERSION', 'v19.0'),
    ],

    'sslcommerz' => [
        'store_id' => env('SSLC_STORE_ID'),
        'store_password' => env('SSLC_STORE_PASSWORD'),
        'sandbox' => env('SSLC_SANDBOX', true),
        'currency' => env('SSLC_STORE_CURRENCY', 'BDT'),
    ],

    'steadfast' => [
        'base_url' => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),
        'api_key' => env('STEADFAST_API_KEY'),
        'secret_key' => env('STEADFAST_SECRET_KEY'),
        // Bearer token entered in Steadfast portal › API › Webhook; webhooks without it are rejected.
        'webhook_secret' => env('STEADFAST_WEBHOOK_SECRET'),
        'webhook_header' => env('STEADFAST_WEBHOOK_HEADER', 'Authorization'),
        // {code} = tracking code
        'tracking_url' => env('STEADFAST_TRACKING_URL', 'https://steadfast.com.bd/t/{code}'),
    ],

    'pathao' => [
        // Sandbox: https://courier-api-sandbox.pathao.com
        'base_url' => env('PATHAO_BASE_URL', 'https://api-hermes.pathao.com'),
        'client_id' => env('PATHAO_CLIENT_ID'),
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
        'username' => env('PATHAO_USERNAME'),
        'password' => env('PATHAO_PASSWORD'),
        'store_id' => env('PATHAO_STORE_ID'),
        'webhook_secret' => env('PATHAO_WEBHOOK_SECRET'),
        'webhook_header' => env('PATHAO_WEBHOOK_HEADER', 'X-PATHAO-Signature'),
        // {consignment_id}, {phone}
        'tracking_url' => env('PATHAO_TRACKING_URL', 'https://merchant.pathao.com/tracking?consignment_id={consignment_id}&phone={phone}'),
    ],

];
