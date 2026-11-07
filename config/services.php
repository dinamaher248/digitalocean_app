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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'app1-8687c'),
        'topic' => env('FIREBASE_TOPIC', 'motorent'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'credentials_storage' => env(
            'FIREBASE_CREDENTIALS_STORAGE',
            'app/firebase/app1-8687c-firebase-adminsdk.json'
        ),
    ],

    'wallet' => [
        'transfer_number' => env('WALLET_TRANSFER_NUMBER'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
