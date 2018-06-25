<?php

return [
    'cache_duration'           => 60 * 60 * 24 * 30,
    'database_connection_name' => 'mysql',
    'connection_mask_prefix'   => 'ecommerce_',
    'data_mode'                => 'host',

    'table_prefix' => 'ecommerce_',
    'brand'        => 'drumeo',

    //the countries and the region names should be lowercase
    'tax_rate'     => [
        'canada' => [
            'alberta'                   => 0.05,
            'ab'                        => 0.05,
            'british columbia'          => 0.12,
            'bc'                        => 0.12,
            'manitoba'                  => 0.05,
            'mb'                        => 0.05,
            'new brunswick'             => 0.13,
            'nb'                        => 0.13,
            'newfoundland'              => 0.13,
            'nl'                        => 0.13,
            'newfoundland and labrador' => 0.13,
            'northwest territories'     => 0.05,
            'nt'                        => 0.05,
            'nova scotia'               => 0.15,
            'ns'                        => 0.15,
            'nunavut'                   => 0.05,
            'nu'                        => 0.05,
            'ontario'                   => 0.13,
            'on'                        => 0.13,
            'prince edward island'      => 0.14,
            'pe'                        => 0.14,
            'pei'                       => 0.14,
            'quebec'                    => 0.05,
            'qc'                        => 0.05,
            'saskatchewan'              => 0.05,
            'sk'                        => 0.05,
            'yukon'                     => 0.05,
            'yt'                        => 0.05,
        ],
    ],

    'supported_currencies' => [
        'CAD',
        'USD',
        'GBP',
        'EUR',
    ],

    'default_currency'                    => 'USD',
    'default_currency_pair_price_offsets' => [
        'CAD' => [
            97 => 125,
        ],
        'GBP' => [
            97 => 73,
        ],
        'EUR' => [
            97 => 84,
        ],
    ],

    'payment_gateways' => [
        'paypal' => [
            'drumeo'   => [
                'paypal_api_username'                  => '',
                'paypal_api_password'                  => '',
                'paypal_api_signature'                 => '',
                'paypal_api_currency_code'             => '',
                'paypal_api_version'                   => '',
                'paypal_api_nvp_curl_url'              => '',
                'paypal_api_checkout_redirect_url'     => '',
                'paypal_api_checkout_return_url'       => '',
                'paypal_api_checkout_cancel_url'       => '',
                'paypal_api_test_billing_agreement_id' => '',
            ],
            'recordeo' => [
                'paypal_api_username'                  => '',
                'paypal_api_password'                  => '',
                'paypal_api_signature'                 => '',
                'paypal_api_currency_code'             => '',
                'paypal_api_version'                   => '',
                'paypal_api_nvp_curl_url'              => '',
                'paypal_api_checkout_redirect_url'     => '',
                'paypal_api_checkout_return_url'       => '',
                'paypal_api_checkout_cancel_url'       => '',
                'paypal_api_test_billing_agreement_id' => '',
            ],
        ],

        'stripe' => [
            'drumeo'   => [
                'stripe_api_secret'      => '',
                'stripe_publishable_key' => '',
            ],
            'recordeo' => [
                'stripe_api_secret'      => '',
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

    'role_abilities'          => [
        'administrator' => [
            'create.shipping.option',
            'edit.shipping.option',
            'delete.shipping.option',
            'pull.shipping.options',

            'update.payment.method',
            'delete.payment.method',

            'create.payment',
            'delete.payment',

            'pull.orders',
            'delete.order',

            'pull.subscriptions',
            'delete.subscription',

            'pull.discounts',

            'pull.fulfillments',
            'fulfilled.fulfillment'
        ],
    ],
    'invoiceSender'           => 'support@drumeo.com',
    'invoiceSenderName'       => 'Drumeo',
    'invoiceAddress'          => 'Drumeo 107-31265 Wheel Avenue - Abbotsford BC, Canada',
    'invoiceEmailSubject'     => 'Order Invoice - Thank You!',
    'paymentPlanOptions'      => [1, 2, 5],
    'paymentPlanMinimumPrice' => 20,

    'billingAddress'              => 'billing',
    'shippingAddress'             => 'shipping',
    'paypalPaymentMethodType'     => 'paypal',
    'creditCartPaymentMethodType' => 'credit-card',
    'manualPaymentMethodType'     => 'manual',
    'orderPaymentType'            => 'order',
    'renewalPaymentType'          => 'renewal',
    'typeProduct'                 => 'product',
    'typeSubscription'            => 'subscription',
    'typePaymentPlan'             => 'payment plan',
    'intervalTypeDaily'           => 'day',
    'intervalTypeMonthly'         => 'month',
    'intervalTypeYearly'          => 'year',
    'fulfillmentStatusPending'    => 'pending',
    'fulfillmentStatusFulfilled'  => 'fulfilled'
];