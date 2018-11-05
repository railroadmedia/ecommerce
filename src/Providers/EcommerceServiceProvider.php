<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PDO;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Decorators\AccessCodeDecorator;
use Railroad\Ecommerce\Decorators\AddressEntityDecorator;
use Railroad\Ecommerce\Decorators\DiscountDiscountCriteriaDecorator;
use Railroad\Ecommerce\Decorators\MethodDecorator;
use Railroad\Ecommerce\Decorators\OrderItemFulfillmentAddressDecorator;
use Railroad\Ecommerce\Decorators\OrderItemProductDecorator;
use Railroad\Ecommerce\Decorators\OrderOrderItemsDecorators;
use Railroad\Ecommerce\Decorators\PaymentMethodBillingAddressDecorator;
use Railroad\Ecommerce\Decorators\PaymentMethodEntityDecorator;
use Railroad\Ecommerce\Decorators\PaymentMethodOwnerDecorator;
use Railroad\Ecommerce\Decorators\PaymentOrderDecorator;
use Railroad\Ecommerce\Decorators\PaymentPaymentMethodDecorator;
use Railroad\Ecommerce\Decorators\PaymentSubscriptionDecorator;
use Railroad\Ecommerce\Decorators\PaymentSubscriptionDecoratorDecorator;
use Railroad\Ecommerce\Decorators\PaymentUserDecorator;
use Railroad\Ecommerce\Decorators\ProductDecorator;
use Railroad\Ecommerce\Decorators\ProductDiscountDecorator;
use Railroad\Ecommerce\Decorators\ProductEntityDecorator;
use Railroad\Ecommerce\Decorators\ShippingOptionsCostsDecorator;
use Railroad\Ecommerce\Decorators\SubscriptionPaymentMethodDecorator;
use Railroad\Ecommerce\Decorators\SubscriptionProductDecorator;
use Railroad\Ecommerce\Entities\Cart;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Listeners\UserDefaultPaymentMethodListener;
use Railroad\Ecommerce\Listeners\UserProductListener;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomValidationRules;
use Railroad\Resora\Events\Created;
use Railroad\Resora\Events\Updated;

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
            GiveContentAccess::class => [GiveContentAccessListener::class . '@handle'],
            UserDefaultPaymentMethodEvent::class => [UserDefaultPaymentMethodListener::class],
            Created::class => [UserProductListener::class.'@handleCreated'],
            Updated::class => [UserProductListener::class.'@handleUpdated']
        ];

        parent::boot();

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/ecommerce.php' => config_path('ecommerce.php'),
            ]
        );

        if (ConfigService::$dataMode == 'host') {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        //load package views file (email template)
        $this->loadViewsFrom(__DIR__ . '/../../views', 'ecommerce');

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
            'resora.decorators.address',
            array_merge(
                config()->get('resora.decorators.address', []),
                [
                    AddressEntityDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.product',
            array_merge(
                config()->get('resora.decorators.product', []),
                [
                    ProductEntityDecorator::class,
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
                    PaymentOrderDecorator::class,
                    PaymentSubscriptionDecorator::class
                ]
            )
        );

        config()->set(
            'resora.decorators.subscription',
            array_merge(
                config()->get('resora.decorators.subscription', []),
                [
                    SubscriptionPaymentMethodDecorator::class,
                    SubscriptionProductDecorator::class,
                ]
            )
        );
        config()->set(
            'resora.decorators.order',
            array_merge(
                config()->get('resora.decorators.order', []),
                [
                    OrderOrderItemsDecorators::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.orderItem',
            array_merge(
                config()->get('resora.decorators.orderItem', []),
                [
                    OrderItemProductDecorator::class,
                ]
            )
        );


        config()->set(
            'resora.decorators.discount',
            array_merge(
                config()->get('resora.decorators.discount', []),
                [
                    DiscountDiscountCriteriaDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.discountCriteria',
            array_merge(
                config()->get('resora.decorators.discountCriteria', []),
                [
                    SubscriptionProductDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.shippingOptions',
            array_merge(
                config()->get('resora.decorators.shippingOptions', []),
                [
                    ShippingOptionsCostsDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.orderItemFulfillment',
            array_merge(
                config()->get('resora.decorators.orderItemFulfillment', []),
                [
                    OrderItemFulfillmentAddressDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.userPaymentMethods',
            array_merge(
                config()->get('resora.decorators.userPaymentMethods', []),
                [
                    PaymentPaymentMethodDecorator::class,
                    PaymentMethodEntityDecorator::class,
                ]
            )
        );

        config()->set(
            'resora.decorators.accessCode',
            array_merge(
                config()->get('resora.decorators.accessCode', []),
                [
                    AccessCodeDecorator::class,
                ]
            )
        );
    }

    private function setupConfig()
    {
        // caching
        ConfigService::$cacheTime = config('ecommerce.cache_duration');

        // database
        ConfigService::$databaseConnectionName = config('ecommerce.database_connection_name');
        ConfigService::$connectionMaskPrefix = config('ecommerce.connection_mask_prefix');
        ConfigService::$dataMode = config('ecommerce.data_mode');

        // tables
        ConfigService::$tablePrefix = config('ecommerce.table_prefix');

        ConfigService::$tableProduct = ConfigService::$tablePrefix . 'product';
        ConfigService::$tableOrder = ConfigService::$tablePrefix . 'order';
        ConfigService::$tableOrderItem = ConfigService::$tablePrefix . 'order_item';
        ConfigService::$tableAddress = ConfigService::$tablePrefix . 'address';
        ConfigService::$tableCustomer = ConfigService::$tablePrefix . 'customer';
        ConfigService::$tableOrderPayment = ConfigService::$tablePrefix . 'order_payment';
        ConfigService::$tablePayment = ConfigService::$tablePrefix . 'payment';
        ConfigService::$tablePaymentMethod = ConfigService::$tablePrefix . 'payment_method';
        ConfigService::$tableCreditCard = ConfigService::$tablePrefix . 'credit_card';
        ConfigService::$tableRefund = ConfigService::$tablePrefix . 'refund';
        ConfigService::$tableSubscription = ConfigService::$tablePrefix . 'subscription';
        ConfigService::$tableSubscriptionPayment = ConfigService::$tablePrefix . 'subscription_payment';
        ConfigService::$tableDiscount = ConfigService::$tablePrefix . 'discount';
        ConfigService::$tableDiscountCriteria = ConfigService::$tablePrefix . 'discount_criteria';
        ConfigService::$tableOrderDiscount = ConfigService::$tablePrefix . 'order_discount';
        ConfigService::$tableOrderItemFulfillment = ConfigService::$tablePrefix . 'order_item_fulfillment';
        ConfigService::$tableShippingOption = ConfigService::$tablePrefix . 'shipping_option';
        ConfigService::$tableShippingCostsWeightRange = ConfigService::$tablePrefix . 'shipping_costs_weight_range';
        ConfigService::$tablePaypalBillingAgreement = ConfigService::$tablePrefix . 'paypal_billing_agreement';
        ConfigService::$tableCustomerPaymentMethods = ConfigService::$tablePrefix . 'customer_payment_methods';
        ConfigService::$tableUserPaymentMethods = ConfigService::$tablePrefix . 'user_payment_methods';
        ConfigService::$tableCustomerStripeCustomer = ConfigService::$tablePrefix . 'customer_stripe_customer';
        ConfigService::$tableUserStripeCustomer = ConfigService::$tablePrefix . 'user_stripe_customer';
        ConfigService::$tablePaymentGateway = ConfigService::$tablePrefix . 'payment_gateway';
        ConfigService::$tableUserProduct = ConfigService::$tablePrefix . 'user_product';
        ConfigService::$tableAccessCode = ConfigService::$tablePrefix . 'access_code';

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
        ConfigService::$typeProduct = config('ecommerce.typeProduct');

        //subscription type
        ConfigService::$typeSubscription = config('ecommerce.typeSubscription');

        //payment plan type
        ConfigService::$paymentPlanType = config('ecommerce.typePaymentPlan');

        //shipping address type
        ConfigService::$shippingAddressType = config('ecommerce.shippingAddress');

        //billing address type
        ConfigService::$billingAddressType = config('ecommerce.billingAddress');

        //payment method types
        ConfigService::$paypalPaymentMethodType = config('ecommerce.paypalPaymentMethodType');
        ConfigService::$creditCartPaymentMethodType = config('ecommerce.creditCartPaymentMethodType');
        ConfigService::$manualPaymentType = config('ecommerce.manualPaymentMethodType');

        //payment types
        ConfigService::$orderPaymentType = config('ecommerce.orderPaymentType');
        ConfigService::$renewalPaymentType = config('ecommerce.renewalPaymentType');

        //subscription interval types
        ConfigService::$intervalTypeDaily = config('ecommerce.intervalTypeDaily');
        ConfigService::$intervalTypeMonthly = config('ecommerce.intervalTypeMonthly');
        ConfigService::$intervalTypeYearly = config('ecommerce.intervalTypeYearly');

        //shipping fulfillment status
        ConfigService::$fulfillmentStatusPending = config('ecommerce.fulfillmentStatusPending');
        ConfigService::$fulfillmentStatusFulfilled = config('ecommerce.fulfillmentStatusFulfilled');

        // currencies
        ConfigService::$supportedCurrencies = config('ecommerce.supported_currencies');
        ConfigService::$defaultCurrency = config('ecommerce.default_currency');
        ConfigService::$currencyExchangeRates = config('ecommerce.currency_exchange_rates');

        // paypal
        ConfigService::$paypalAgreementRoute = config('ecommerce.paypal.agreementRoute');
        ConfigService::$paypalAgreementFulfilledRoute = config('ecommerce.paypal.agreementFulfilledRoute');

        ConfigService::$subscriptionRenewalDateCutoff = config('ecommerce.subscription_renewal_date');
        ConfigService::$failedPaymentsBeforeDeactivation = config('ecommerce.failed_payments_before_de_activation');
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