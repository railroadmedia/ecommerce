<?php

return [
    'development_mode' => true,

    // brands
    'brand' => 'brand',
    'available_brands' => ['brand'],

    // database
    'database_connection_name' => 'mysql',
    'database_name' => env('DB_DATABASE'),
    'database_user' => env('DB_USERNAME'),
    'database_password' => env('DB_PASSWORD'),
    'database_host' => env('DB_HOST'),
    'database_driver' => 'pdo_mysql',
    'database_in_memory' => false,

    // host does the db migrations, clients do not
    'data_mode' => 'host', // 'host' or 'client'

    // cache
    'redis_host' => 'redis',
    'redis_port' => 6379,

    'entities' => [
        [
            'path' => __DIR__ . '/../src/Entities',
            'namespace' => 'Railroad\Ecommerce\Entities',
        ],
    ],

    // routes
    'autoload_all_routes' => true,
    'route_middleware_public_groups' => ['ecommerce_public'],
    'route_middleware_logged_in_groups' => ['ecommerce_logged_in'],
    'route_prefix' => '',

    //the countries and the region names should be lowercase
    'tax_rate' => [
        'canada' => [
            'default' => 0.5,
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

    'supported_currencies' => [
        'CAD',
        'USD',
        'GBP',
        'EUR',
    ],

    'default_currency' => 'USD',
    'default_currency_conversion_rates' => [
        'CAD' => 1.32,
        'EUR' => 0.88,
        'GBP' => 0.77,
        'USD' => 1.0,
    ],
    
    'financing_cost_per_order' => 1,
    'payment_plan_options' => 1, 2, 5,

    'default_gateway' => 'drumeo',
    'payment_gateways' => [
        'paypal' => [
            'drumeo' => [
                'paypal_api_username' => '',
                'paypal_api_password' => '',
                'paypal_api_signature' => '',
                'paypal_api_currency_code' => '',
                'paypal_api_version' => '',
                'paypal_api_nvp_curl_url' => '',
                'paypal_api_checkout_redirect_url' => 'https://www.sandbox.paypal.com/checkoutnow/2?useraction=commit&token=',
                'paypal_api_checkout_return_url' => '',
                'paypal_api_checkout_cancel_url' => '',
                'paypal_api_test_billing_agreement_id' => '',
            ],
            'recordeo' => [
                'paypal_api_username' => '',
                'paypal_api_password' => '',
                'paypal_api_signature' => '',
                'paypal_api_currency_code' => '',
                'paypal_api_version' => '',
                'paypal_api_nvp_curl_url' => '',
                'paypal_api_checkout_redirect_url' => '',
                'paypal_api_checkout_return_url' => '',
                'paypal_api_checkout_cancel_url' => '',
                'paypal_api_test_billing_agreement_id' => '',
            ],
            'brand' => [
                'paypal_api_username' => '',
                'paypal_api_password' => '',
                'paypal_api_signature' => '',
                'paypal_api_currency_code' => '',
                'paypal_api_version' => '',
                'paypal_api_nvp_curl_url' => '',
                'paypal_api_checkout_redirect_url' => '',
                'paypal_api_checkout_return_url' => '',
                'paypal_api_checkout_cancel_url' => '',
                'paypal_api_test_billing_agreement_id' => '',
            ],
        ],

        'stripe' => [
            'drumeo' => [
                'stripe_api_secret' => '',
                'stripe_publishable_key' => '',
            ],
            'recordeo' => [
                'stripe_api_secret' => '',
                'stripe_publishable_key' => '',
            ],
        ],
    ],

    'middleware' => [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ],

    'paypal' => [
        'agreement_route' => 'payment-method.paypal.agreement',
        'agreement_fulfilled_route' => ''
        // route to redirect after handling a paypal agreement, eg for recordeo 'account.settings.payments'
    ],

    'role_abilities' => [
        'administrator' => [
            'create.shipping.option',
            'edit.shipping.option',
            'delete.shipping.option',
            'pull.shipping.options',

            'update.payment.method',
            'delete.payment.method',
            'pull.user.payment.method',

            'create.payment',
            'delete.payment',

            'pull.orders',
            'edit.order',
            'delete.order',

            'pull.subscriptions',
            'edit.subscription',
            'delete.subscription',

            'pull.discounts',

            'pull.fulfillments',
            'fulfilled.fulfillment',
            'delete.fulfillment',

            'pull.access_codes',
            'claim.access_codes',
            'release.access_codes',

            'pull.user.address',
        ],
    ],

    'invoice_sender' => 'support@drumeo.com',
    'invoice_sender_name' => 'Drumeo',
    'invoice_address' => 'Drumeo 107-31265 Wheel Avenue - Abbotsford BC, Canada',
    'invoice_email_subject' => 'Order Invoice - Thank You!',

    'payment_plan_options' => [1, 2, 5],
    'payment_plan_minimum_price' => 20,

    'billing_address' => 'billing',
    'shipping_address' => 'shipping',
    'paypal_payment_method_type' => 'paypal',
    'credit_cart_payment_method_type' => 'credit-card',
    'manual_payment_method_type' => 'manual',
    'order_payment_type' => 'order',
    'renewal_payment_type' => 'renewal',
    'type_product' => 'product',
    'type_subscription' => 'subscription',
    'type_payment_plan' => 'payment plan',
    'interval_type_daily' => 'day',
    'interval_type_monthly' => 'month',
    'interval_type_yearly' => 'year',
    'fulfillment_status_pending' => 'pending',
    'fulfillment_status_fulfilled' => 'fulfilled',

    'subscription_renewal_date' => 1,
    'failed_payments_before_de_activation' => 1,
];