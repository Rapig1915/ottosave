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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'braintree' => [
        'env' => env('BRAINTREE_ENV', 'sandbox'),
        'merchant_id' => env('BRAINTREE_MERCHANT_ID', null),
        'public_key' => env('BRAINTREE_PUBLIC_KEY', '123'),
        'private_key' => env('BRAINTREE_PRIVATE_KEY', '123'),
    ],

    'itunes' => [
        'env' => env('ITUNES_ENV', 'sandbox'),
        'shared_secret' => env('ITUNES_SECRET', '')
    ],

    'active_campaign' => [
        'key' => env('ACTIVE_CAMPAIGN_API_KEY'),
        'list_id' => env('ACTIVE_CAMPAIGN_LIST_ID'),
        'url' => env('ACTIVE_CAMPAIGN_URL')
    ],

    'userflow' => [
        'token' => env('USERFLOW_TOKEN')
    ],

    'tapfiliate' => [
        'api_key' => env('TAPFILIATE_API_KEY'),
        'account_prefix' => env('TAPFILIATE_ACCOUNT_PREFIX', 'local-dev')
    ]

];
