<?php

return [
    'base_url' => env('KOBOPOINT_BASE_URL', 'https://app.kobopoint.com/api/v1'),
    'secret_key' => env('KOBOPOINT_SECRET_KEY'),
    'business_id' => env('KOBOPOINT_BUSINESS_ID'),
    'api_key' => env('KOBOPOINT_API_KEY'),
    'verify_ssl' => env('KOBOPOINT_VERIFY_SSL', true),
];
