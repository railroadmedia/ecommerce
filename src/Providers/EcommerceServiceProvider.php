<?php

namespace Railroad\Ecommerce\Providers;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Gedmo\DoctrineExtensions;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Doctrine\TimestampableListener;
use Railroad\Ecommerce\Commands\FillPaymentGatewayColumnFromPaymentMethod;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Commands\SplitPaymentMethodIdsToColumns;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Listeners\OrderInvoiceListener;
use Railroad\Ecommerce\Listeners\OrderShippingFulfilmentListener;
use Railroad\Ecommerce\Listeners\UserDefaultPaymentMethodListener;
use Railroad\Ecommerce\Listeners\OrderUserProductListener;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomValidationRules;
use Railroad\Ecommerce\Types\UserType;
use Redis;

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
            OrderEvent::class => [
                OrderShippingFulfilmentListener::class,
                OrderUserProductListener::class,
                OrderInvoiceListener::class,
            ],
        ];

        parent::boot();

        $this->setupConfig();

        $this->publishes([
                __DIR__ . '/../../config/ecommerce.php' => config_path('ecommerce.php'),
            ]);

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
                SplitPaymentMethodIdsToColumns::class,
                FillPaymentGatewayColumnFromPaymentMethod::class,
            ]
        );

        $this->app->validator->resolver(function ($translator, $data, $rules, $messages, $attributes) {
            return new CustomValidationRules($translator, $data, $rules, $messages, $attributes);
        });
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
        ConfigService::$tableProduct = 'ecommerce_products';
        ConfigService::$tableOrder = 'ecommerce_orders';
        ConfigService::$tableOrderItem = 'ecommerce_order_items';
        ConfigService::$tableAddress = 'ecommerce_addresses';
        ConfigService::$tableCustomer = 'ecommerce_customers';
        ConfigService::$tableOrderPayment = 'ecommerce_order_payments';
        ConfigService::$tablePayment = 'ecommerce_payments';
        ConfigService::$tablePaymentMethod = 'ecommerce_payment_methods';
        ConfigService::$tableCreditCard = 'ecommerce_credit_cards';
        ConfigService::$tableRefund = 'ecommerce_refunds';
        ConfigService::$tableSubscription = 'ecommerce_subscriptions';
        ConfigService::$tableSubscriptionPayment = 'ecommerce_subscription_payments';
        ConfigService::$tableDiscount = 'ecommerce_discounts';
        ConfigService::$tableDiscountCriteria = 'ecommerce_discount_criteria';
        ConfigService::$tableOrderDiscount = 'ecommerce_order_discounts';
        ConfigService::$tableOrderItemFulfillment = 'ecommerce_order_item_fulfillment';
        ConfigService::$tableShippingOption = 'ecommerce_shipping_options';
        ConfigService::$tableShippingCostsWeightRange = 'ecommerce_shipping_costs_weight_ranges';
        ConfigService::$tablePaypalBillingAgreement = 'ecommerce_paypal_billing_agreements';
        ConfigService::$tableCustomerPaymentMethods = 'ecommerce_customer_payment_methods';
        ConfigService::$tableUserPaymentMethods = 'ecommerce_user_payment_methods';
        ConfigService::$tableUserStripeCustomerId = 'ecommerce_user_stripe_customer_ids';
        ConfigService::$tablePaymentGateway = 'ecommerce_payment_gateways';
        ConfigService::$tableUserProduct = 'ecommerce_user_products';
        ConfigService::$tableAccessCode = 'ecommerce_access_codes';
        ConfigService::$tableSubscriptionAccessCode = 'ecommerce_subscription_access_codes';

        // brand
        ConfigService::$brand = config('ecommerce.brand');
        ConfigService::$availableBrands = config('ecommerce.available_brands');

        //tax rated
        ConfigService::$taxRate = config('ecommerce.tax_rate');

        //credit card
        ConfigService::$creditCard = config('ecommerce.credit_card');

        //paypal API
        ConfigService::$paymentGateways = config('ecommerce.payment_gateways');

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

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    private function setupEntityManager()
    {
        !Type::hasType(UserType::USER_TYPE) ? Type::addType(UserType::USER_TYPE, UserType::class) : null;

        // set proxy dir to temp folder
        if (app()->runningUnitTests()) {
            $proxyDir = sys_get_temp_dir();
        } else {
            $proxyDir = sys_get_temp_dir() . '/railroad/ecommerce/proxies';
        }

        // setup redis
        $redis = new Redis();
        $redis->connect(config('ecommerce.redis_host'), config('ecommerce.redis_port'));

        $redisCache = new RedisCache();
        $redisCache->setRedis($redis);

        app()->instance('EcommerceRedisCache', $redisCache);
        app()->instance('EcommerceArrayCache', new ArrayCache());

        // annotation reader
        AnnotationRegistry::registerLoader('class_exists');

        $annotationReader = new AnnotationReader();

        $cachedAnnotationReader =
            new CachedReader($annotationReader, $redisCache, config('ecommerce.development_mode'));

        $driverChain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain, $cachedAnnotationReader);

        // entities
        foreach (config('ecommerce.entities') as $driverConfig) {
            $annotationDriver = new AnnotationDriver($cachedAnnotationReader, $driverConfig['path']);

            $driverChain->addDriver($annotationDriver, $driverConfig['namespace']);
        }

        // timestamps
        $timestampableListener = new TimestampableListener();
        $timestampableListener->setAnnotationReader($cachedAnnotationReader);

        // soft deletes
        $softDeletesListener = new SoftDeleteableListener();
        $softDeletesListener->setAnnotationReader($cachedAnnotationReader);

        // event manager
        $eventManager = new EventManager();
        $eventManager->addEventSubscriber($timestampableListener);
        $eventManager->addEventSubscriber($softDeletesListener);

        // orm config
        $ormConfiguration = new Configuration();
        $ormConfiguration->setMetadataCacheImpl($redisCache);
        $ormConfiguration->setQueryCacheImpl($redisCache);
        $ormConfiguration->setResultCacheImpl($redisCache);
        $ormConfiguration->setProxyDir($proxyDir);
        $ormConfiguration->setProxyNamespace('DoctrineProxies');
        $ormConfiguration->setAutoGenerateProxyClasses(config('ecommerce.development_mode'));
        $ormConfiguration->setMetadataDriverImpl($driverChain);
        $ormConfiguration->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $ormConfiguration->addFilter('soft-deleteable', SoftDeleteableFilter::class);

        // database config
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

        $entityManager = EcommerceEntityManager::create($databaseOptions, $ormConfiguration, $eventManager);

        $entityManager->getFilters()->enable('soft-deleteable');

        if (config('ecommerce.enable_query_log')) {
            $logger = new EchoSQLLogger();

            $entityManager->getConnection()->getConfiguration()->setSQLLogger( $logger);
        }

        app()->instance(EcommerceEntityManager::class, $entityManager);
    }
}