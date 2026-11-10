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

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo Weather API Integration (P12)
    |--------------------------------------------------------------------------
    |
    | Open-Meteo provides free, open-source weather forecasts without requiring
    | an API key for compliant non-commercial usage.
    |
    */
    'open_meteo' => [
        'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
        'timeout' => (int) env('OPEN_METEO_TIMEOUT', 3),
        'connect_timeout' => (int) env('OPEN_METEO_CONNECT_TIMEOUT', 2),
        'cache_ttl' => (int) env('OPEN_METEO_CACHE_TTL', 1800),
        'enabled' => filter_var(env('OPEN_METEO_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenStreetMap Nominatim Geocoding API Integration (P12)
    |--------------------------------------------------------------------------
    |
    | OpenStreetMap Nominatim provides forward and reverse geocoding services.
    | Compliance requires a valid User-Agent header and rate-limit considerations.
    |
    */
    'geocoding' => [
        'base_url' => env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'timeout' => (int) env('GEOCODING_TIMEOUT', 3),
        'connect_timeout' => (int) env('GEOCODING_CONNECT_TIMEOUT', 2),
        'cache_ttl' => (int) env('GEOCODING_CACHE_TTL', 86400),
        'enabled' => filter_var(env('GEOCODING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'user_agent' => env('GEOCODING_USER_AGENT', 'CargoTrace-Platform/1.0 (contact@cargotrace.test)'),
    ],

];
