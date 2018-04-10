<?php

return [
    'cache_duration' => 60 * 60 * 24 * 30,
    'database_connection_name' => 'mysql',
    'connection_mask_prefix' => 'ecommerce_',
    'data_mode' => 'host',

    'table_prefix' => 'ecommerce_',
    'brand' => 'drumeo',
    //the countries and the region names should be lowercase
    'tax_rate' => [
        'canada' => [
            'alberta' => 0.05,
            'ab' => 0.05,
            'british columbia' => 0.12,
            'bc' => 0.12,
            'manitoba' => 0.05,
            'mb' => 0.05,
            'new brunswick' => 0.13,
            'nb' => 0.13,
            'newfoundland' => 0.13,
            'nl' => 0.13,
            'newfoundland and labrador' => 0.13,
            'northwest territories' => 0.05,
            'nt' => 0.05,
            'nova scotia' => 0.15,
            'ns' => 0.15,
            'nunavut' => 0.05,
            'nu' => 0.05,
            'ontario' => 0.13,
            'on' => 0.13,
            'prince edward island' => 0.14,
            'pe' => 0.14,
            'pei' => 0.14,
            'quebec' => 0.05,
            'qc' => 0.05,
            'saskatchewan' => 0.05,
            'sk' => 0.05,
            'yukon' => 0.05,
            'yt' => 0.05
        ]
    ],
    'credit_card' =>
    [
        'external_provider' => 'stripe'
    ],
    'paypal' => [
        'paypal_api_username' => env('PAYPAL_API_USERNAME',''),
        'paypal_api_password' => env('PAYPAL_API_PASSWORD',''),
        'paypal_api_signature' => env('PAYPAL_API_SIGNATURE',''),
        'paypal_api_currency_code' => env('PAYPAL_API_CURRENCY_CODE', ''),
        'paypal_api_version' => env('PAYPAL_API_VERSION',''),
        'paypal_api_nvp_curl_url' => env('PAYPAL_API_NVP_CURL_URL', ''),
        'paypal_api_checkout_redirect_url' => env('PAYPAL_API_CHECKOUT_REDIRECT_URL', ''),
        'paypal_api_test_billing_agreement_id' => env('PAYPAL_API_TEST_BILLING_AGREEMENT_ID','')
    ],
    'stripe' => [
        'stripe_api_secret' => env('STRIPE_API_SECRET',''),
        'stripe_publishable_key' => env('STRIPE_PUBLISHABLE_KEY','')
    ]
];