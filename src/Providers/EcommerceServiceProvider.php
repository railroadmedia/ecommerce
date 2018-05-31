<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PDO;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Decorators\DiscountDiscountCriteriaDecorator;
use Railroad\Ecommerce\Decorators\MethodDecorator;
use Railroad\Ecommerce\Decorators\PaymentMethodBillingAddressDecorator;
use Railroad\Ecommerce\Decorators\PaymentMethodEntityDecorator;
use Railroad\Ecommerce\Decorators\PaymentMethodOwnerDecorator;
use Railroad\Ecommerce\Decorators\PaymentPaymentMethodDecorator;
use Railroad\Ecommerce\Decorators\PaymentUserDecorator;
use Railroad\Ecommerce\Decorators\ProductDecorator;
use Railroad\Ecommerce\Decorators\ProductDiscountDecorator;
use Railroad\Ecommerce\Decorators\SubscriptionPaymentMethodDecorator;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomValidationRules;

class EcommerceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->listen = [
            StatementPrepared::class => [
                function (StatementPrepared $event) {

                    // we only want to use assoc fetching for this packages database calls
                    // so we need to use a separate 'mask' connection

                    if($event->connection->getName() ==
                        ConfigService::$connectionMaskPrefix . ConfigService::$databaseConnectionName)
                    {
                        $event->statement->setFetchMode(PDO::FETCH_ASSOC);
                    }
                },
            ],
            GiveContentAccess::class => [GiveContentAccessListener::class . '@handle'],
        ];

        parent::boot();

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/ecommerce.php' => config_path('ecommerce.php'),
            ]
        );

        if(ConfigService::$dataMode == 'host')
        {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        //load package routes file
        $this->loadRoutesFrom(__DIR__ . '/../../routes/routes.php');

        $this->commands(
            [
                RenewalDueSubscriptions::class,
            ]
        );

        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages) {
                return new CustomValidationRules($translator, $data, $rules, $messages);
            }
        );

        config()->set(
            'resora.decorators.product',
            array_merge(
                config()->get('resora.decorators.product', []),
                [
                    ProductDecorator::class,
                    ProductDiscountDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.paymentMethod',
            array_merge(
                config()->get('resora.decorators.paymentMethod', []),
                [
                    MethodDecorator::class,
                    PaymentMethodOwnerDecorator::class,
                    PaymentMethodEntityDecorator::class,
                    PaymentMethodBillingAddressDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.payment',
            array_merge(
                config()->get('resora.decorators.payment', []),
                [
                    PaymentPaymentMethodDecorator::class,
                    PaymentUserDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.subscription',
            array_merge(
                config()->get('resora.decorators.subscription', []),
                [
                    SubscriptionPaymentMethodDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.discount',
            array_merge(
                config()->get('resora.decorators.discount', []),
                [
                    DiscountDiscountCriteriaDecorator::class
                ]
            )
        );

        // merge in permissions settings
        config()->set(
            'permissions.role_abilities',
            array_merge(
                config()->get('permissions.role_abilities', []),
                config()->get('ecommerce.role_abilities', [])
            )
        );
    }

    private function setupConfig()
    {
        // caching
        ConfigService::$cacheTime = config('ecommerce.cache_duration');

        // database
        ConfigService::$databaseConnectionName = config('ecommerce.database_connection_name');
        ConfigService::$connectionMaskPrefix   = config('ecommerce.connection_mask_prefix');
        ConfigService::$dataMode               = config('ecommerce.data_mode');

        // tables
        ConfigService::$tablePrefix = config('ecommerce.table_prefix');

        ConfigService::$tableProduct                  = ConfigService::$tablePrefix . 'product';
        ConfigService::$tableOrder                    = ConfigService::$tablePrefix . 'order';
        ConfigService::$tableOrderItem                = ConfigService::$tablePrefix . 'order_item';
        ConfigService::$tableAddress                  = ConfigService::$tablePrefix . 'address';
        ConfigService::$tableCustomer                 = ConfigService::$tablePrefix . 'customer';
        ConfigService::$tableOrderPayment             = ConfigService::$tablePrefix . 'order_payment';
        ConfigService::$tablePayment                  = ConfigService::$tablePrefix . 'payment';
        ConfigService::$tablePaymentMethod            = ConfigService::$tablePrefix . 'payment_method';
        ConfigService::$tableCreditCard               = ConfigService::$tablePrefix . 'credit_card';
        ConfigService::$tableRefund                   = ConfigService::$tablePrefix . 'refund';
        ConfigService::$tableSubscription             = ConfigService::$tablePrefix . 'subscription';
        ConfigService::$tableSubscriptionPayment      = ConfigService::$tablePrefix . 'subscription_payment';
        ConfigService::$tableDiscount                 = ConfigService::$tablePrefix . 'discount';
        ConfigService::$tableDiscountCriteria         = ConfigService::$tablePrefix . 'discount_criteria';
        ConfigService::$tableOrderDiscount            = ConfigService::$tablePrefix . 'order_discount';
        ConfigService::$tableOrderItemFulfillment     = ConfigService::$tablePrefix . 'order_item_fulfillment';
        ConfigService::$tableShippingOption           = ConfigService::$tablePrefix . 'shipping_option';
        ConfigService::$tableShippingCostsWeightRange = ConfigService::$tablePrefix . 'shipping_costs_weight_range';
        ConfigService::$tablePaypalBillingAgreement   = ConfigService::$tablePrefix . 'paypal_billing_agreement';
        ConfigService::$tableCustomerPaymentMethods   = ConfigService::$tablePrefix . 'customer_payment_methods';
        ConfigService::$tableUserPaymentMethods       = ConfigService::$tablePrefix . 'user_payment_methods';
        ConfigService::$tableCustomerStripeCustomer   = ConfigService::$tablePrefix . 'customer_stripe_customer';
        ConfigService::$tableUserStripeCustomer       = ConfigService::$tablePrefix . 'user_stripe_customer';
        ConfigService::$tablePaymentGateway           = ConfigService::$tablePrefix . 'payment_gateway';

        // brand
        ConfigService::$brand = config('ecommerce.brand');

        //tax rated
        ConfigService::$taxRate = config('ecommerce.tax_rate');

        //credit card
        ConfigService::$creditCard = config('ecommerce.credit_card');

        //paypal API
        ConfigService::$paymentGateways = config('ecommerce.payment_gateways');

        // middleware
        ConfigService::$middleware = config('ecommerce.middleware', []);

        //product type
        ConfigService::$typeProduct = 'product';

        //subscription type
        ConfigService::$typeSubscription = 'subscription';

        //shipping address type
        ConfigService::$shippingAddressType = 'shipping';

        //billing address type
        ConfigService::$billingAddressType = 'billing';

        // currencies
        ConfigService::$supportedCurrencies = config('ecommerce.supported_currencies');
        ConfigService::$defaultCurrency = config('ecommerce.default_currency');
        ConfigService::$defaultCurrencyPairPriceOffsets = config('ecommerce.default_currency_pair_price_offsets');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}