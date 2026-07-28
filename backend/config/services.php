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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/').'/api/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/').'/api/auth/facebook/callback'),
    ],

    'mapbox' => [
        'key' => env('MAPBOX_API_KEY'),
    ],

    'tomtom' => [
        'key' => env('TOMTOM_API_KEY'),
    ],

    'here' => [
        'key' => env('HERE_API_KEY'),
    ],

    'sepay' => [
        'bank' => env('SEPAY_BANK'),
        'account_number' => env('SEPAY_ACCOUNT_NUMBER'),
        'account_name' => env('SEPAY_ACCOUNT_NAME'),
        'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY'),
        'payment_prefix' => env('SEPAY_PAYMENT_PREFIX', 'DH'),
        'store_name' => env('SEPAY_STORE_NAME', env('APP_NAME', 'NextGen')),
    ],

];
