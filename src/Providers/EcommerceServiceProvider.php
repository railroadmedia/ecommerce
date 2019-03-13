<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Listeners\UserDefaultPaymentMethodListener;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomValidationRules;



use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Doctrine\TimestampableListener;

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
        $this->loadRoutesFrom(__DIR__ . '/../../routes/access_codes.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/address.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/discount.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/discount_criteria.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/order.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/order_form.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/payment.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/payment_method.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/product.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/refund.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/session.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_costs.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_fulfillment.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_option.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/shopping_cart.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/stats.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/stripe_webhook.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/subscriptions.php');

        $this->commands(
            [
                RenewalDueSubscriptions::class,
            ]
        );

        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages, $attributes) {
                return new CustomValidationRules($translator, $data, $rules, $messages, $attributes);
            }
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
        ConfigService::$tableSubscriptionAccessCode = ConfigService::$tablePrefix . 'subscription_access_code';

        // brand
        ConfigService::$brand = config('ecommerce.brand');
        ConfigService::$availableBrands = config('ecommerce.available_brands');

        //tax rated
        ConfigService::$taxRate = config('ecommerce.tax_rate');

        //credit card
        ConfigService::$creditCard = config('ecommerce.credit_card');

        //paypal API
        ConfigService::$paymentGateways = config('ecommerce.payment_gateways');

        // middleware
        ConfigService::$middleware = config('ecommerce.middleware', []);

        //product type
        ConfigService::$typeProduct = config('ecommerce.type_product');

        //subscription type
        ConfigService::$typeSubscription = config('ecommerce.type_subscription');

        //payment plan type
        ConfigService::$paymentPlanType = config('ecommerce.type_payment_plan');

        //shipping address type
        ConfigService::$shippingAddressType = config('ecommerce.shipping_address');

        //billing address type
        ConfigService::$billingAddressType = config('ecommerce.billing_address');

        //payment method types
        ConfigService::$paypalPaymentMethodType = config('ecommerce.paypal_payment_method_type');
        ConfigService::$creditCartPaymentMethodType = config('ecommerce.credit_cart_payment_method_type');
        ConfigService::$manualPaymentType = config('ecommerce.manual_payment_method_type');

        //payment types
        ConfigService::$orderPaymentType = config('ecommerce.order_payment_type');
        ConfigService::$renewalPaymentType = config('ecommerce.renewal_payment_type');

        //subscription interval types
        ConfigService::$intervalTypeDaily = config('ecommerce.interval_type_daily');
        ConfigService::$intervalTypeMonthly = config('ecommerce.interval_type_monthly');
        ConfigService::$intervalTypeYearly = config('ecommerce.interval_type_yearly');

        //shipping fulfillment status
        ConfigService::$fulfillmentStatusPending = config('ecommerce.fulfillment_status_pending');
        ConfigService::$fulfillmentStatusFulfilled = config('ecommerce.fulfillment_status_fulfilled');

        // currencies
        ConfigService::$supportedCurrencies = config('ecommerce.supported_currencies');
        ConfigService::$defaultCurrency = config('ecommerce.default_currency');
        ConfigService::$defaultCurrencyConversionRates = config('ecommerce.default_currency_conversion_rates');

        // paypal
        ConfigService::$paypalAgreementRoute = config('ecommerce.paypal.agreement_route');
        ConfigService::$paypalAgreementFulfilledRoute = config('ecommerce.paypal.agreement_fulfilled_route');

        ConfigService::$subscriptionRenewalDateCutoff = config('ecommerce.subscription_renewal_date');
        ConfigService::$failedPaymentsBeforeDeactivation = config('ecommerce.failed_payments_before_de_activation');
    }

    public function register()
    {
        $this->setupEntityManager();
    }

    private function setupEntityManager()
    {
        // temp setup - needs confirmation

        $redisCache = app()->make(RedisCache::class);

        $annotationReader = new AnnotationReader();

        $cachedAnnotationReader = new CachedReader(
            $annotationReader, $redisCache
        );

        $driverChain = app()->make(MappingDriverChain::class);

        foreach (config('ecommerce.entities') as $driverConfig) {
            $annotationDriver = new AnnotationDriver(
                $cachedAnnotationReader, $driverConfig['path']
            );

            $driverChain->addDriver(
                $annotationDriver,
                $driverConfig['namespace']
            );
        }

        $eventManager = app()->make(EventManager::class);

        $ormConfiguration = app()->make(Configuration::class);

        if (config('ecommerce.database_in_memory') !== true) {
            $databaseOptions = [
                'driver' => config('ecommerce.database_driver'),
                'dbname' => config('ecommerce.database_name'),
                'user' => config('ecommerce.database_user'),
                'password' => config('ecommerce.database_password'),
                'host' => config('ecommerce.database_host'),
            ];
        } else {
            $databaseOptions = [
                'driver' => config('ecommerce.database_driver'),
                'user' => config('ecommerce.database_user'),
                'password' => config('ecommerce.database_password'),
                'memory' => true,
            ];
        }

        $entityManager = EcommerceEntityManager::create(
            $databaseOptions,
            $ormConfiguration,
            $eventManager
        );

        app()->instance(EcommerceEntityManager::class, $entityManager);
    }
}