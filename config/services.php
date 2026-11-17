<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'openweathermap' => [
        'key' => env('OPENWEATHERMAP_API_KEY'),
        'base_url' => env('OPENWEATHERMAP_BASE_URL', 'https://api.openweathermap.org'),
        'geocoding_path' => '/geo/1.0/direct',
        'weather_path' => '/data/2.5/weather',
        'language' => env('OPENWEATHERMAP_LANGUAGE', 'ru'),
        'cache_ttl' => (int) env('OPENWEATHERMAP_CACHE_TTL', 600),
        'timeout' => (int) env('OPENWEATHERMAP_TIMEOUT', 5),
        'retry_times' => (int) env('OPENWEATHERMAP_RETRY_TIMES', 2),
        'retry_sleep' => (int) env('OPENWEATHERMAP_RETRY_SLEEP', 200),
    ],

];
