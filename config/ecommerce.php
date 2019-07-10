<?php

return [
    'development_mode' => env('APP_DEBUG', true),

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
    'enable_query_log' => false,

    // unique user validation database info
    'database_info_for_unique_user_email_validation' => [
        'database_connection_name' => 'mysql',
        'table' => 'users',
        'email_column' => 'email',
    ],

    // host does the db migrations, clients do not
    'data_mode' => 'host', // 'host' or 'client'

    // cache
    'redis_host' => 'redis',
    'redis_port' => 6379,

    // entities
    'entities' => [
        [
            'path' => __DIR__ . '/../src/Entities',
            'namespace' => 'Railroad\Ecommerce\Entities',
        ],
    ],

    // routes
    'route_prefix' => 'ecommerce',
    'autoload_all_routes' => true,
    'route_middleware_public_groups' => ['ecommerce_public'],
    'route_middleware_logged_in_groups' => ['ecommerce_logged_in'],

    //the countries and the region names should be lowercase
    'product_tax_rate' => [
        'canada' => [
            'alberta' => 0.05,
            'british columbia' => 0.12,
            'manitoba' => 0.05,
            'new brunswick' => 0.15,
            'newfoundland and labrador' => 0.15,
            'northwest territories' => 0.05,
            'nova scotia' => 0.15,
            'nunavut' => 0.05,
            'ontario' => 0.13,
            'prince edward island' => 0.15,
            'quebec' => 0.05,
            'saskatchewan' => 0.05,
            'yukon' => 0.05,
        ],
    ],

    // tax rate used on the shipping costs, its sometimes different than tax for product cost
    'shipping_tax_rate' => [
        'canada' => [
            'alberta' => 0.05,
            'british columbia' => 0.05,
            'manitoba' => 0.05,
            'new brunswick' => 0.15,
            'newfoundland and labrador' => 0.15,
            'northwest territories' => 0.05,
            'nova scotia' => 0.15,
            'nunavut' => 0.05,
            'ontario' => 0.13,
            'prince edward island' => 0.15,
            'quebec' => 0.05,
            'saskatchewan' => 0.05,
            'yukon' => 0.05,
        ],
    ],

    // this is used to show how much of the taxes the user paid went to gst in invoices
    'gst_hst_tax_rate_display_only' => [
        'canada' => [
            'alberta' => 0.05,
            'british columbia' => 0.05,
            'manitoba' => 0.05,
            'new brunswick' => 0.15,
            'newfoundland and labrador' => 0.15,
            'northwest territories' => 0.05,
            'nova scotia' => 0.15,
            'nunavut' => 0.05,
            'ontario' => 0.13,
            'prince edward island' => 0.15,
            'quebec' => 0.05,
            'saskatchewan' => 0.05,
            'yukon' => 0.05,
        ],
    ],

    // currencies
    'supported_currencies' => [
        'CAD',
        'USD',
        'GBP',
        'EUR',
    ],

    // changing the default currency can have massive consequences
    'default_currency' => 'USD',
    'default_currency_conversion_rates' => [
        'CAD' => 1.32,
        'EUR' => 0.88,
        'GBP' => 0.77,
        'USD' => 1.0,
    ],

    // payment plans
    'financing_cost_per_order' => 1,
    'payment_plan_options' => [1, 2, 5],
    'payment_plan_minimum_price' => 20,

    // gateways
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
                'paypal_api_checkout_return_route' => 'order-form.submit-paypal',
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
            'brand' => [
                'stripe_api_secret' => '',
                'stripe_publishable_key' => '',
            ],
        ],

        'apple_store_kit' => [
            'endpoint' => 'https://sandbox.itunes.apple.com/verifyReceipt',
            'shared_secret' => 'b1ab16b41296400bbf10431d72386f5f',
        ],

        'google_paly_store' => [

        ],
    ],

    'apple_store_products_map' => [
        'apple_store_product_id_one' => 'local_product_sku_one',
        'apple_store_product_id_two' => 'local_product_sku_two',
    ],

    // paypal
    'paypal' => [
        'agreement_route' => 'payment-method.paypal.agreement',
        'agreement_fulfilled_path' => '/test'
    ],

    // permissions
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

            'pull.addresses',
            'update.address',
            'delete.address',
        ],
    ],

    // product access day offset
    'days_before_access_revoked_after_expiry' => 5,

    // invoices
    'invoice_email_details' => [
        'brand' => [
            'subscription_renewal_invoice' => [
                'invoice_sender' => 'support@pianote.com',
                'invoice_sender_name' => 'Pianote',
                'invoice_address' => 'Pianote 107-31265 Wheel Avenue - Abbotsford BC, Canada',
                'invoice_email_subject' => 'Pianote Invoice - Thank You!',
                'invoice_view' => 'ecommerce::subscription_renewal_invoice',
            ],
            'order_invoice' => [
                'invoice_sender' => 'support@pianote.com',
                'invoice_sender_name' => 'Pianote',
                'invoice_address' => 'Pianote 107-31265 Wheel Avenue - Abbotsford BC, Canada',
                'invoice_email_subject' => 'Pianote Invoice - Thank You!',
                'invoice_view' => 'ecommerce::order_invoice',
            ],
        ],
    ],

    // redirects
    'post_purchase_redirect_digital_items' => '/members',

    // constants
    'billing_address' => 'billing',
    'shipping_address' => 'shipping',
    'paypal_payment_method_type' => 'paypal',
    'credit_cart_payment_method_type' => 'credit-card',
    'manual_payment_method_type' => 'manual',
    'order_payment_type' => Railroad\Ecommerce\Entities\Payment::TYPE_INITIAL_ORDER,
    'renewal_payment_type' => Railroad\Ecommerce\Entities\Payment::TYPE_SUBSCRIPTION_RENEWAL,
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