<?php

return [
    'beams' => [
        'instance_id' => env('PUSHER_BEAMS_INSTANCE_ID'),
        'secret_key' => env('PUSHER_BEAMS_SECRET_KEY'),
    ],
    'fcm' => [
        // FCM HTTP v1: path to (or raw JSON of) a Firebase service account key.
        'credentials' => env('FCM_CREDENTIALS'),
        // Optional; defaults to the project_id inside the service account JSON.
        'project_id' => env('FCM_PROJECT_ID'),
        'notification_icon' => env('FCM_NOTIFICATION_ICON', 'quikmedix_notification'),
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],
    'zadarma' => [
        'key' => env('ZADARMA_KEY'),
        'secret' => env('ZADARMA_SECRET'),
        'sip' => env('ZADARMA_SIP'),
    ],
    'bestrx' => [
        'basic_auth' => env('BESTRX_BASIC_AUTH'),
    ],
    'tidio' => [
        'widget_key' => env('TIDIO_WIDGET_KEY'),
    ],

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

];
