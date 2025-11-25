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
        'secret' => env('POSTMARK_TOKEN'),
    ],

    'sendinblue' => [
        'key_identifier' => env('SENDINBLUE_KEY_IDENTIFIER', 'api-key'),
        'key' => env('SENDINBLUE_KEY'),
    ],

    'mailerlite' => [
        'key' => env('MAILERLITE_API_KEY'),
        'group' => env('MAILERLITE_GROUP_ID'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'recaptcha' => [
        'key' => env('GOOGLE_RECAPTCHA_KEY'),
        'secret' => env('GOOGLE_RECAPTCHA_SECRET'),
    ],

    'beehiv' => [
        'key' => env('BEEHIV_API_KEY'),
        'base_url' => env('BEEHIV_BASE_URL', 'https://api.beehiiv.com/v2'),
    ],

    'turrence' => [
        'key' => env('TURRENCE_API_KEY'),
    ],

    'axcess' => [
        'token' => env('AXCESS_TOKEN'),
    ],
];
