<?php

return [
    'development_mode' => env('APP_DEBUG', true),

    // brands
    'brand' => 'drumeo',
    'available_brands' => ['drumeo'],

    // accounting report timezone
    'accounting_report_timezone' => 'America/Los_Angeles',

    // database
    'database_connection_name' => 'mysql',
    'database_name' => env('DB_DATABASE'),
    'database_user' => env('DB_USERNAME'),
    'database_password' => env('DB_PASSWORD'),
    'database_host' => env('DB_HOST'),
    'database_driver' => 'pdo_mysql',
    'database_charset' => 'UTF8',
    'database_in_memory' => false,
    'enable_query_log' => false,

    // brand membership product skus
    'membership_product_skus' => [
        'drumeo' => [
            'DLM-1-month',
            'DLM-1-year',
            'DLM-Trial-1-month',
            'DLM-6-month',
            'DLM-teachers-1-year',
            'DLM-teachers-upgrade-1-month',
            'DLM-teachers-upgrade-1-year',
            'DLM-3-month',
            'DFT-PASS-1_old-1-month',
            'DLM-UPSELL-2-month',
            'DLM-Trial-Best-Book-1-month',
            'DLM-Trial-Drummers-Toolbox-1-month',
            'DLM-Lifetime',
            'drumeo_edge_30_days_access',
            'DLM-Trial-30-Day',
        ],
    ],

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

    // tax config
    'country_province_codes' => [
        'canada' => [
            'ab' => 'Alberta',
            'bc' => 'British Columbia',
            'mb' => 'Manitoba',
            'nb' => 'New Brunswick',
            'nl' => 'Newfoundland and Labrador',
            'nt' => 'Northwest Territories',
            'ns' => 'Nova Scotia',
            'nu' => 'Nunavut',
            'on' => 'Ontario',
            'pe' => 'Prince Edward Island',
            'pei' => 'Prince Edward Island',
            'qc' => 'Quebec',
            'sk' => 'Saskatchewan',
            'yt' => 'Yukon',
        ]
    ],

    'tax_rates_and_options' => [
        'canada' => [
            'alberta' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'british columbia' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
                [
                    'type' => 'PST',
                    'rate' => 0.07,
                    'applies_to_shipping_costs' => false,
                ],
            ],
            'manitoba' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
                // PST does not apply until a sales threshold is hit
                [
                    'type' => 'PST',
                    'rate' => 0.07,
                    'applies_to_shipping_costs' => false,
                ],
            ],
            'new brunswick' => [
                [
                    'type' => 'HST',
                    'rate' => 0.15,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'newfoundland and labrador' => [
                [
                    'type' => 'HST',
                    'rate' => 0.15,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'northwest territories' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'nova scotia' => [
                [
                    'type' => 'HST',
                    'rate' => 0.15,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'nunavut' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'ontario' => [
                [
                    'type' => 'HST',
                    'rate' => 0.13,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'prince edward island' => [
                [
                    'type' => 'HST',
                    'rate' => 0.15,
                    'applies_to_shipping_costs' => true,
                ],
            ],
            'quebec' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
                // QST does not apply until a sales threshold is hit
                [
                    'type' => 'QST',
                    'rate' => 0.09975,
                    'applies_to_shipping_costs' => false,

                    // do not apply this tax for the given payment gateways (brands)
                    'gateway_blacklist' => ['pianote', 'guitareo'],
                ],
            ],
            'saskatchewan' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
                // PST does not apply until a sales threshold is hit
                [
                    'type' => 'PST',
                    'rate' => 0.07,
                    'applies_to_shipping_costs' => false,
                ],
            ],
            'yukon' => [
                [
                    'type' => 'GST',
                    'rate' => 0.05,
                    'applies_to_shipping_costs' => true,
                ],
            ],
        ],
    ],

    'recommended_products_count' => 3,

    'recommended_products' => [
        'drumeo' => [
            [
                'sku' => 'DLM-Trial-1-month',
                'name_override' => 'Drumeo Edge 7-Day Trial',
                'excluded_skus' => [
                    'DLM-1-month',
                    'DLM-1-year',
                    'DLM-Trial-1-month',
                    'DLM-6-month',
                    'DLM-teachers-1-year',
                    'DLM-teachers-upgrade-1-month',
                    'DLM-teachers-upgrade-1-year',
                    'DLM-3-month',
                    'DLM-UPSELL-2-month',
                    'DLM-Trial-Best-Book-1-month',
                    'edge-membership-6-months',
                    'DLM-Trial-Drummers-Toolbox-1-month',
                    'DLM-Lifetime',
                    'drumeo_edge_30_days_access',
                    'DLM-Trial-30-Day',
                ],
                'cta' => '7 Days Free, Then $29/mo',
            ],
            [
                'sku' => 'quietpad',
            ],
            [
                'sku' => 'Drumeo-VaterSticks',
            ],
            [
                'sku' => 'the-drummers-toolbox-book',
            ],
            [
                'sku' => 'BeginnerBook',
            ],
            [
                'sku' => 'tone-control-kit',
            ],
        ],
        'pianote' => [
            [
                'sku' => 'PIANOTE-MEMBERSHIP-TRIAL',
                'name_override' => 'Pianote 7-Day Trial',
                'excluded_skus' => [
                    'PIANOTE-MEMBERSHIP-1-MONTH',
                    'PIANOTE-MEMBERSHIP-1-YEAR',
                    'PIANOTE-MEMBERSHIP-LIFETIME',
                    'PIANOTE-MEMBERSHIP-LIFETIME-EXISTING-MEMBERS',
                    '1-DOLLAR',
                    'PIANOTE-MEMBERSHIP-6-MONTH',
                    'PIANOTE-MEMBERSHIP-TRIAL',
                    'PIANOTE-MEMBERSHIP-TRIAL-30-DAY',
                ],
                'cta' => '7 Days Free, Then $29/mo',
            ],
            [
                'sku' => 'pianote-foundation',
            ],
            [
                'sku' => 'Sweatshirt-Hooded-Black-S',
                'name_override' => 'Iconic Pianote Hoodie',
                'excluded_skus' => [
                    'Sweatshirt-Hooded-Black-S',
                    'Sweatshirt-Hooded-Black-M',
                    'Sweatshirt-Hooded-Black-L',
                    'Sweatshirt-Hooded-Black-XL',
                    'Sweatshirt-Hooded-Black-XXL',
                    'Sweatshirt-Hooded-Black-XXXL',
                ],
                'cta' => 'See Details',
                'add_directly_to_cart' => false,
            ],
            [
                'sku' => '2019-TSHIRT-S',
                'name_override' => 'Iconic Pianote T-Shirt',
                'excluded_skus' => [
                    '2019-TSHIRT-S',
                    '2019-TSHIRT-M',
                    '2019-TSHIRT-L',
                    '2019-TSHIRT-XL',
                    '2019-TSHIRT-XXL',
                ],
                'cta' => 'See Details',
                'add_directly_to_cart' => false,
            ],
            [
                'sku' => 'Tshirt-Floral-Black-XS',
                'name_override' => "Women's Floral Shirt",
                'excluded_skus' => [
                    'Tshirt-Floral-Black-XS',
                    'Tshirt-Floral-Black-S',
                    'Tshirt-Floral-Black-L',
                    'Tshirt-Floral-Black-M',
                    'Tshirt-Floral-Black-XL',
                ],
                'cta' => 'See Details',
                'add_directly_to_cart' => false,
            ],
        ],
    ],

//
//    //the countries and the region names should be lowercase
//    'product_tax_rate' => [
//        'canada' => [
//            'alberta' => 0.05,
//            'british columbia' => 0.12,
//            'manitoba' => 0.05,
//            'new brunswick' => 0.15,
//            'newfoundland and labrador' => 0.15,
//            'northwest territories' => 0.05,
//            'nova scotia' => 0.15,
//            'nunavut' => 0.05,
//            'ontario' => 0.13,
//            'prince edward island' => 0.15,
//            'quebec' => 0.05,
//            'saskatchewan' => 0.05,
//            'yukon' => 0.05,
//        ],
//    ],
//
//    // tax rate used on the shipping costs, its sometimes different than tax for product cost
//    'shipping_tax_rate' => [
//        'canada' => [
//            'alberta' => 0.05,
//            'british columbia' => 0.05,
//            'manitoba' => 0.05,
//            'new brunswick' => 0.15,
//            'newfoundland and labrador' => 0.15,
//            'northwest territories' => 0.05,
//            'nova scotia' => 0.15,
//            'nunavut' => 0.05,
//            'ontario' => 0.13,
//            'prince edward island' => 0.15,
//            'quebec' => 0.05,
//            'saskatchewan' => 0.05,
//            'yukon' => 0.05,
//        ],
//    ],
//
//    // this is used to show how much of the taxes the user paid went to gst in invoices
//    'gst_hst_tax_rate_display_only' => [
//        'canada' => [
//            'alberta' => 0.05,
//            'british columbia' => 0.05,
//            'manitoba' => 0.05,
//            'new brunswick' => 0.15,
//            'newfoundland and labrador' => 0.15,
//            'northwest territories' => 0.05,
//            'nova scotia' => 0.15,
//            'nunavut' => 0.05,
//            'ontario' => 0.13,
//            'prince edward island' => 0.15,
//            'quebec' => 0.05,
//            'saskatchewan' => 0.05,
//            'yukon' => 0.05,
//        ],
//    ],
//
//    // this is used to show how much of the taxes the user paid went to pst in invoices
//    'pst_tax_rate_display_only' => [
//        'canada' => [
//            'british columbia' => 0.05,
//        ],
//    ],
//
//    'qst_tax_rate' => [
//        'canada' => [
//            'quebec' => 0.09975,
//        ]
//    ],

    // this is displayed on all invoices to canadian customers
    'canada_gst_hst_number' => [
        'brand' => '12345 1512312',
    ],

    // displayed on invoices
    'company_name_on_invoice' => 'My Company',

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
    'payment_plan_minimum_price_with_physical_items' => 1,
    'payment_plan_minimum_price_without_physical_items' => 1,

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

        'google_play_store' => [
            'credentials' => '/app/ecommerce/api.json',
            'application_name' => 'com.drumeo',
            'scope' => ['https://www.googleapis.com/auth/androidpublisher'],
        ],
    ],

    'apple_store_products_map' => [
        'apple_store_product_id_one' => 'local_product_sku_one',
        'apple_store_product_id_two' => 'local_product_sku_two',
    ],

    'google_store_products_map' => [
        'google_store_product_id_one' => 'local_product_sku_one',
        'google_store_product_id_two' => 'local_product_sku_two',
    ],

    // paypal
    'paypal' => [
        'agreement_route' => 'payment-method.paypal.agreement',
        'agreement_fulfilled_path' => '/test'
    ],

    // membership subscription duplicate syncing
    'membership_product_syncing_info' => [
        'brand_1' => [
            'membership_product_skus' => [
                'SKU-1',
                'SKU-2',
            ],
        ],
        'brand_2' => [
            'membership_product_skus' => [
                'SKU-5',
                'SKU-6',
            ],
        ],
    ],

    // attempt_number => hours after initial renewal due date
    'subscriptions_renew_cycles' => [
        1 => 8,
        2 => 24 * 3,
        3 => 24 * 7,
        4 => 24 * 14,
        5 => 24 * 30,
    ],

    // the system will not try and renew subscriptions which expired before this date
    // this is for when launching the 2.4 update, since we don't want to re-bill a bunch of old subscriptions on launch
    'subscription_renewal_attempt_system_start_date' => '2020-04-16 00:00:00',

    // permissions
    'role_abilities' => [
        'administrator' => [
            'create.shipping.option',
            'edit.shipping.option',
            'delete.shipping.option',
            'pull.shipping.options',

            'pull.customers',
            'update.customers',

            'update.payment.method',
            'delete.payment.method',
            'pull.user.payment.method',
            'pull.customer.payment.method',

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

            'show_deleted',

            'list.failed-billing',

            'send_payment_invoice',

            'pull.accounting',
        ],
    ],

    // product access day offset
    'days_before_access_revoked_after_expiry' => 5,
    'days_before_access_revoked_after_expiry_in_app_purchases_only' => 3,

    // invoices
    'invoice_email_details' => [
        'drumeo' => [
            'subscription_renewal_invoice' => [
                'invoice_sender' => 'support@pianote.com',
                'invoice_sender_name' => 'Pianote',
                'invoice_address' => 'Pianote 107-31265 Wheel Avenue - Abbotsford BC, Canada',
                'invoice_email_subject' => 'Pianote Invoice - Thank You!',
                'invoice_view' => 'ecommerce::subscription_renewal_invoice',
            ],

            // the order invoice is also used for payment plan renewals
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
    'post_purchase_redirect_customer_order' => '/thankyou',

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

    'subscription_renewal_date' => 90, // todo - to be removed
    'failed_payments_before_de_activation' => 1,

    'trial_days_number' => 7,

    'password_creation_rules' => 'confirmed|min:8|max:128', // also defined in usora

    'membership_product_skus_for_code_redeem' => [],

    'code_redeem_product_sku_swap' => [],

    /**
     * Currencies supported by the API.
     *
     * @var array
     */

    'allowable_currencies' => [
        'AED',
        'AFN',
        'ALL',
        'AMD',
        'ANG',
        'AOA',
        'ARS',
        'AUD',
        'AWG',
        'AZN',
        'BAM',
        'BBD',
        'BDT',
        'BGN',
        'BHD',
        'BIF',
        'BMD',
        'BND',
        'BOB',
        'BRL',
        'BSD',
        'BTN',
        'BWP',
        'BYN',
        'BZD',
        'CAD',
        'CDF',
        'CHF',
        'CLP',
        'CNY',
        'COP',
        'CRC',
        'CUC',
        'CUP',
        'CVE',
        'CZK',
        'DJF',
        'DKK',
        'DOP',
        'DZD',
        'EGP',
        'ERN',
        'ETB',
        'EUR',
        'FJD',
        'FKP',
        'FOK',
        'GBP',
        'GEL',
        'GGP',
        'GHS',
        'GIP',
        'GMD',
        'GNF',
        'GTQ',
        'GYD',
        'HKD',
        'HNL',
        'HRK',
        'HTG',
        'HUF',
        'IDR',
        'ILS',
        'IMP',
        'INR',
        'IQD',
        'IRR',
        'ISK',
        'JMD',
        'JOD',
        'JPY',
        'KES',
        'KGS',
        'KHR',
        'KID',
        'KMF',
        'KRW',
        'KWD',
        'KYD',
        'KZT',
        'LAK',
        'LBP',
        'LKR',
        'LRD',
        'LSL',
        'LYD',
        'MAD',
        'MDL',
        'MGA',
        'MKD',
        'MMK',
        'MNT',
        'MOP',
        'MRU',
        'MUR',
        'MVR',
        'MWK',
        'MXN',
        'MYR',
        'MZN',
        'NAD',
        'NGN',
        'NIO',
        'NOK',
        'NPR',
        'NZD',
        'OMR',
        'PAB',
        'PEN',
        'PGK',
        'PHP',
        'PKR',
        'PLN',
        'PYG',
        'QAR',
        'RON',
        'RSD',
        'RUB',
        'RWF',
        'SAR',
        'SBD',
        'SCR',
        'SDG',
        'SEK',
        'SGD',
        'SHP',
        'SLL',
        'SOS',
        'SRD',
        'SSP',
        'STN',
        'SYP',
        'SZL',
        'THB',
        'TJS',
        'TMT',
        'TND',
        'TOP',
        'TRY',
        'TTD',
        'TVD',
        'TWD',
        'TZS',
        'UAH',
        'UGX',
        'USD',
        'UYU',
        'UZS',
        'VES',
        'VND',
        'VUV',
        'WST',
        'XAF',
        'XCD',
        'XDR',
        'XOF',
        'XPF',
        'YER',
        'ZAR',
        'ZMW',
    ],

    // exchangerate-api.com
    'exchange_rate_api_token' => 'api-key'
];