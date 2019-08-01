<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'cloudflare' => [
        'secret' => env('CLOUDFLARE_SECRET'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'route53' => [
        'key' => env('ROUTE_53_KEY'),
        'secret' => env('ROUTE_53_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'testing' => [
        'digital_ocean' => env('DIGITAL_OCEAN_TEST_KEY'),
        'github' => env('GITHUB_TEST_KEY'),
        's3_key' => env('S3_KEY'),
        's3_secret' => env('S3_SECRET'),
    ],

];
