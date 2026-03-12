<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PointWave API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PointWave virtual account provider.
    | These values are loaded from .env and cached when running config:cache
    |
    */

    'api_key' => env('POINTWAVE_API_KEY'),
    'secret_key' => env('POINTWAVE_SECRET_KEY'),
    'business_id' => env('POINTWAVE_BUSINESS_ID'),
    'base_url' => env('POINTWAVE_BASE_URL', 'https://app.pointwave.ng/api/v1'),
    'webhook_secret' => env('POINTWAVE_WEBHOOK_SECRET'),
];
