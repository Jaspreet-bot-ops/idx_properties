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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'trestle' => [
        'client_id' => env('TRESTLE_CLIENT_ID'),
        'client_secret' => env('TRESTLE_CLIENT_SECRET'),
        'scope' => env('TRESTLE_SCOPE', 'api'),
        'grant_type' => env('TRESTLE_GRANT_TYPE', 'client_credentials'),
    ],
    
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'bridge' => [
        'url' => env('BRIDGE_API_URL', 'https://api.bridgedataoutput.com/api/v2'),
        'key' => env('BRIDGE_API_KEY', "f091fc0d25a293957350aa6a022ea4fb"),
        'endpoint' => env('BRIDGE_API_ENDPOINT', 'miamire/listings'),
    ],

];
