<?php

namespace Railroad\Ecommerce\Providers;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\DoctrineExtensions;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Railroad\Doctrine\TimestampableListener;
use Railroad\Ecommerce\Commands\AddPastMembershipStats;
use Railroad\Ecommerce\Commands\ConvertDiscountCriteriaProducsAssociation;
use Railroad\Ecommerce\Commands\FillPaymentGatewayColumnFromPaymentMethod;
use Railroad\Ecommerce\Commands\FindDuplicateSubscriptionsAndLifetimesWithSubscriptions;
use Railroad\Ecommerce\Commands\FixSerializeErrorInAppPurchaseTables;
use Railroad\Ecommerce\Commands\FixSubscriptionTotalAndTaxes;
use Railroad\Ecommerce\Commands\ListDueSubscriptions;
use Railroad\Ecommerce\Commands\MatchOrderItemDiscountAmountToTotal;
use Railroad\Ecommerce\Commands\MembershipsReportingTool;
use Railroad\Ecommerce\Commands\MobileAppGoogleAppleHelper;
use Railroad\Ecommerce\Commands\PopulatePaymentTaxesTable;
use Railroad\Ecommerce\Commands\PopulatePermissionNamesColumnInEcommerceProducts;
use Railroad\Ecommerce\Commands\ProcessAppleExpiredSubscriptions;
use Railroad\Ecommerce\Commands\RenewalDueSubscriptions;
use Railroad\Ecommerce\Commands\RetentionReportingTool;
use Railroad\Ecommerce\Commands\SplitPaymentMethodIdsToColumns;
use Railroad\Ecommerce\Commands\SynchOrdersWithPaymentPlans;
use Railroad\Ecommerce\Commands\UpdateLastDigits;
use Railroad\Ecommerce\Commands\VerifyAppleNotifications;
use Railroad\Ecommerce\Commands\VerifyLocalPriceConversion;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\MobileOrderEvent;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Listeners\DuplicateSubscriptionHandler;
use Railroad\Ecommerce\Listeners\GiveContentAccessListener;
use Railroad\Ecommerce\Listeners\MobileOrderUserProductListener;
use Railroad\Ecommerce\Listeners\OrderInvoiceListener;
use Railroad\Ecommerce\Listeners\OrderShippingFulfilmentListener;
use Railroad\Ecommerce\Listeners\OrderUserProductListener;
use Railroad\Ecommerce\Listeners\SubscriptionInvoiceListener;
use Railroad\Ecommerce\Listeners\UserDefaultPaymentMethodListener;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CustomValidationRules;
use Railroad\Ecommerce\Types\UserType;
use Redis;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
//            UserDefaultPaymentMethodEvent::class => [UserDefaultPaymentMethodListener::class],
            OrderEvent::class => [
                OrderShippingFulfilmentListener::class,
                OrderUserProductListener::class,
                OrderInvoiceListener::class,
                DuplicateSubscriptionHandler::class,
            ],
            SubscriptionRenewed::class => [SubscriptionInvoiceListener::class],
            MobileOrderEvent::class => [MobileOrderUserProductListener::class]
        ];

        parent::boot();

        $this->publishes(
            [
                __DIR__ . '/../../config/ecommerce.php' => config_path('ecommerce.php'),
            ]
        );

        if (config('ecommerce.data_mode') == 'host') {
            $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        }

        //load package views file (email template)
        $this->loadViewsFrom(__DIR__ . '/../../views', 'ecommerce');

        //load package routes file
        if (config('ecommerce.autoload_all_routes', true) == true) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/access_codes.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/accounting.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/address.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/customer.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/discount.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/discount_criteria.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/membership_stats.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/membership_action.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/order.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/order_form.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/payment.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/payment_method.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/product.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/refund.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/retention_stats.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_costs.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_fulfillment.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/shipping_option.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/shopping_cart.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/stats.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/stripe_webhook.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/subscriptions.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/user_product.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/mobile_app.php');
            $this->loadRoutesFrom(__DIR__ . '/../../routes/paypal_webhook.php');
        }

        // commands
        $this->commands(
            [
                AddPastMembershipStats::class,
                ConvertDiscountCriteriaProducsAssociation::class,
                RenewalDueSubscriptions::class,
                SplitPaymentMethodIdsToColumns::class,
                FillPaymentGatewayColumnFromPaymentMethod::class,
                PopulatePaymentTaxesTable::class,
                ProcessAppleExpiredSubscriptions::class,
                FixSubscriptionTotalAndTaxes::class,
                FindDuplicateSubscriptionsAndLifetimesWithSubscriptions::class,
                FixSerializeErrorInAppPurchaseTables::class,
                MobileAppGoogleAppleHelper::class,
                listDueSubscriptions::class,
                VerifyAppleNotifications::class,
                RetentionReportingTool::class,
                MembershipsReportingTool::class,
                SynchOrdersWithPaymentPlans::class,
                VerifyLocalPriceConversion::class,
                UpdateLastDigits::class,
                MatchOrderItemDiscountAmountToTotal::class,
                PopulatePermissionNamesColumnInEcommerceProducts::class
            ]
        );

        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages, $attributes) {
                return new CustomValidationRules($translator, $data, $rules, $messages, $attributes);
            }
        );
    }

    public function register()
    {
        $this->setupEntityManager();
    }

    private function setupEntityManager()
    {
        !Type::hasType(UserType::USER_TYPE) ? Type::addType(UserType::USER_TYPE, UserType::class) : null;

        // set proxy dir to temp folder
        $proxyDir = sys_get_temp_dir();

        // array cache
        $arrayCacheAdapter = new ArrayAdapter();
        $doctrineArrayCache = DoctrineProvider::wrap($arrayCacheAdapter);
        app()->instance('EcommerceArrayCache', $doctrineArrayCache);

        // file cache
        $phpFileCacheAdapter = new FilesystemAdapter('', 0, $proxyDir);
        $doctrineFileCache = DoctrineProvider::wrap($arrayCacheAdapter);

        // annotation reader
        $annotationReader = new IndexedReader(new AnnotationReader());

        $cachedAnnotationReader = new PsrCachedReader(
            $annotationReader,
            $phpFileCacheAdapter,
            env('APP_DEBUG', false)
        );

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
        $ormConfiguration->setMetadataCache($phpFileCacheAdapter);
        $ormConfiguration->setQueryCache($phpFileCacheAdapter);
        $ormConfiguration->setProxyDir($proxyDir);
        $ormConfiguration->setProxyNamespace('DoctrineProxies');
        $ormConfiguration->setAutoGenerateProxyClasses(
            config('ecommerce.development_mode') ? AbstractProxyFactory::AUTOGENERATE_ALWAYS :
                AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS
        );
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
                'charset' => config('ecommerce.charset', 'UTF8'),
            ];
        }
        else {
            $databaseOptions = [
                'driver' => config('ecommerce.database_driver'),
                'user' => config('ecommerce.database_user'),
                'password' => config('ecommerce.database_password'),
                'memory' => true,
            ];
        }

        $entityManager = EcommerceEntityManager::create($databaseOptions, $ormConfiguration, $eventManager);

        $entityManager->getFilters()
            ->enable('soft-deleteable');

        if (config('ecommerce.enable_query_log')) {
            $logger = new EchoSQLLogger();

            $entityManager->getConnection()
                ->getConfiguration()
                ->setSQLLogger($logger);
        }

        app()->instance(EcommerceEntityManager::class, $entityManager);

        // register global doctrine inflector
        $inflector = InflectorFactory::create()->build();

        app()->instance('DoctrineInflector', $inflector);
    }
}
