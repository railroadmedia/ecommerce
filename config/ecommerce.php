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
            'yt' => 0.05,
        ],
    ],

    'credit_card' =>
        [
            'external_provider' => 'stripe',
        ],

    'paypal' =>
        [
            'paypal_1' => [
                'paypal_api_username' => env('PAYPAL_API_USERNAME', 'jonathan-facilitator_api1.drumeo.com'),
                'paypal_api_password' => env('PAYPAL_API_PASSWORD', '6Q5QN4FAMAGQYJRK'),
                'paypal_api_signature' => env(
                    'PAYPAL_API_SIGNATURE',
                    'AFcWxV21C7fd0v3bYYYRCpSSRl31AzicGtaPYWDX3BZv1bFgMmU9D5mv'
                ),
                'paypal_api_currency_code' => env('PAYPAL_API_CURRENCY_CODE', 'USD'),
                'paypal_api_version' => env('PAYPAL_API_VERSION', '204.0'),
                'paypal_api_nvp_curl_url' => env('PAYPAL_API_NVP_CURL_URL', 'https://api-3t.sandbox.paypal.com/nvp'),
                'paypal_api_checkout_redirect_url' => env(
                    'PAYPAL_API_CHECKOUT_REDIRECT_URL',
                    'https://www.sandbox.paypal.com/checkoutnow/2?useraction=commit&token='
                ),
                'paypal_api_checkout_return_url' => env(
                    'PAYPAL_API_CHECKOUT_RETURN_URL',
                    'http://dev.drumeo.com/laravel/public/order-form/handle-paypal-redirect'
                ),
                'paypal_api_checkout_cancel_url' => env(
                    'PAYPAL_API_CHECKOUT_CANCEL_URL',
                    'http://dev.drumeo.com/laravel/public/paypal-for-railcenter/cancel'
                ),
                'paypal_api_test_billing_agreement_id' => env(
                    'PAYPAL_API_TEST_BILLING_AGREEMENT_ID',
                    'B-1P494577AX649533Y'
                ),
            ],

        ],

    'stripe' => [
        'stripe_1' => [
            'stripe_api_secret' => env('STRIPE_API_SECRET', 'sk_test_yeWqksUTfWjqHUlUp1XD6hSE'),
            'stripe_publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'pk_test_8WbVpdVKKttr3iqIdiT932ME'),
        ],
        'stripe_2' => [
            'stripe_api_secret' => env('STRIPE_API_SECRET', 'sk_test_HhBdvIu40gN9FlxnzJkfB10j'),
            'stripe_publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'pk_test_8WbVpdVKKttr3iqIdiT932ME'),
        ],
    ],

    'middleware' => [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ],

    'role_abilities' => [
        'administrator' => [
            'create.payment_gateway',
            'edit.payment_gateway',
            'delete.payment_gateway',

            'create.shipping_option',
            'edit.shipping_option',
            'delete.shipping_option',

            'update.payment.method',
            'delete.payment.method',

            'create.payment',
        ],
    ]
];