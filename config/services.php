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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'xixapay' => [
        'authorization' => 'Bearer ' . env('XIXAPAY_SECRET_KEY'),
        'api_key' => env('XIXAPAY_API_KEY'),
        'business_id' => env('XIXAPAY_BUSINESS_ID'),
    ],

    'pointwave' => [
        'api_key' => env('POINTWAVE_API_KEY'),
        'secret_key' => env('POINTWAVE_SECRET_KEY'),
        'webhook_secret' => env('POINTWAVE_WEBHOOK_SECRET'), // Deprecated - now uses secret_key
        'base_url' => env('POINTWAVE_BASE_URL', 'https://api.pointwave.ng'),
    ],

];
