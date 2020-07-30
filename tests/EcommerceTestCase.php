<?php

namespace Railroad\Ecommerce\Tests;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\ActionLog\Managers\ActionLogEntityManager;
use Railroad\ActionLog\Providers\ActionLogServiceProvider;
use Railroad\Doctrine\Contracts\UserProviderInterface as DoctrineUserProviderInterface;
use Railroad\Doctrine\Providers\DoctrineServiceProvider;
use Railroad\DoctrineArrayHydrator\Contracts\UserProviderInterface as DoctrineArrayHydratorUserProviderInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AppleReceipt;
use Railroad\Ecommerce\Entities\MembershipStats;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Faker\Factory;
use Railroad\Ecommerce\Gateways\AppleStoreKitGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Providers\EcommerceServiceProvider;
use Railroad\Ecommerce\Tests\Fixtures\UserProvider;
use Railroad\Location\Providers\LocationServiceProvider;
use Railroad\Location\Services\ConfigService;
use Railroad\Permissions\Providers\PermissionsServiceProvider;
use Railroad\Permissions\Services\PermissionService;
use Railroad\RemoteStorage\Providers\RemoteStorageServiceProvider;
use Webpatser\Countries\CountriesServiceProvider;

class EcommerceTestCase extends BaseTestCase
{
    const TABLES = [
        'users' => 'users',
        'products' => 'ecommerce_products',
        'accessCodes' => 'ecommerce_access_codes',
        'subscriptions' => 'ecommerce_subscriptions',
        'addresses' => 'ecommerce_addresses',
        'customers' => 'ecommerce_customers',
        'orderItems' => 'ecommerce_order_items',
        'discounts' => 'ecommerce_discounts',
        'discountCriteria' => 'ecommerce_discount_criteria',
        'shippingOptions' => 'ecommerce_shipping_options',
        'shippingCosts' => 'ecommerce_shipping_costs_weight_ranges',
        'paymentMethods' => 'ecommerce_payment_methods',
        'orders' => 'ecommerce_orders',
        'creditCards' => 'ecommerce_credit_cards',
        'userProducts' => 'ecommerce_user_products',
        'paypalBillingAgreements' => 'ecommerce_paypal_billing_agreements',
        'userPaymentMethod' => 'ecommerce_user_payment_methods',
        'payments' => 'ecommerce_payments',
        'orderPayments' => 'ecommerce_order_payments',
        'subscriptionPayments' => 'ecommerce_subscription_payments',
        'orderItemFulfillments' => 'ecommerce_order_item_fulfillment',
        'refunds' => 'ecommerce_refunds',
        'userStripeCustomerId' => 'ecommerce_user_stripe_customer_ids',
        'discountCriteriasProducts' => 'ecommerce_discount_criterias_products',
        'appleReceipts' => 'ecommerce_apple_receipts',
        'membershipStats' => 'ecommerce_membership_stats',
        'retentionStats' => 'ecommerce_retention_stats',
        'membershipActions' => 'ecommerce_membership_actions',
    ];

    /**
     * @var \Railroad\Ecommerce\Faker\Faker
     */
    protected $faker;

    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AuthManager
     */
    protected $authManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $permissionServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $stripeExternalHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $paypalExternalHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $appleStoreKitGatewayMock;

    /**
     * @var array
     */
    protected $currencies;

    /**
     * @var string
     */
    protected $defaultCurrency;

    /**
     * @var array
     */
    protected $paymentGateway;

    /**
     * @var array
     */
    protected $paymentPlanOptions;

    /**
     * @var float
     */
    protected $paymentPlanMinimumPrice;

    /**
     * @var Application
     */
    protected $app;

    protected function setUp()
    {
        parent::setUp();

        ConfigService::$testingIP = '';

        $this->faker = Factory::create();
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);

        // Run the schema update tool using our entity metadata
        $this->entityManager = app(EcommerceEntityManager::class);

        // todo - find better way to share connection with ActionLogEntityManager
        $actionLogEntitytManager = app(ActionLogEntityManager::class);
        $config = $actionLogEntitytManager->getConfiguration();
        $actionLogEntitytManager->close();
        $actionLogEntitytManager = ActionLogEntityManager::create(
            $this->entityManager->getConnection(),
            $config,
            $this->entityManager->getEventManager()
        );
        $this->app->instance(ActionLogEntityManager::class, $actionLogEntitytManager);

        $this->entityManager->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $this->entityManager->getConfiguration()
            ->getResultCacheImpl()
            ->deleteAll();

        // make sure laravel is using the same connection
        DB::connection()
            ->setPdo(
                $this->entityManager->getConnection()
                    ->getWrappedConnection()
            );
        DB::connection()
            ->setReadPdo(
                $this->entityManager->getConnection()
                    ->getWrappedConnection()
            );

        $this->permissionServiceMock =
            $this->getMockBuilder(PermissionService::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->app->instance(PermissionService::class, $this->permissionServiceMock);

        $this->stripeExternalHelperMock =
            $this->getMockBuilder(\Railroad\Ecommerce\ExternalHelpers\Stripe::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->app->instance(\Railroad\Ecommerce\ExternalHelpers\Stripe::class, $this->stripeExternalHelperMock);

        $this->paypalExternalHelperMock =
            $this->getMockBuilder(\Railroad\Ecommerce\ExternalHelpers\PayPal::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->app->instance(\Railroad\Ecommerce\ExternalHelpers\PayPal::class, $this->paypalExternalHelperMock);

        $this->appleStoreKitGatewayMock = $this->getMockBuilder(AppleStoreKitGateway::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->app->instance(AppleStoreKitGateway::class, $this->appleStoreKitGatewayMock);

        Carbon::setTestNow(Carbon::now());

        $userProvider = new UserProvider();

        $this->app->instance(UserProviderInterface::class, $userProvider);
        $this->app->instance(DoctrineArrayHydratorUserProviderInterface::class, $userProvider);
        $this->app->instance(DoctrineUserProviderInterface::class, $userProvider);

        $this->artisan('migrate:fresh');
        $this->artisan('cache:clear');

        $this->createUsersTable();

        session()->flush();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // setup package config for testing
        $defaultConfig = require(__DIR__ . '/../config/ecommerce.php');
        $locationConfig = require(__DIR__ . '/../vendor/railroad/location/config/location.php');
        $remoteStorageConfig = require(__DIR__ . '/../vendor/railroad/remotestorage/config/remotestorage.php');

        $app['config']->set('ecommerce.database_connection_name', 'testbench');
        $app['config']->set(
            'ecommerce.database_info_for_unique_user_email_validation',
            $defaultConfig['database_info_for_unique_user_email_validation']
        );
        $app['config']->set(
            'ecommerce.database_info_for_unique_user_email_validation.database_connection_name',
            'ecommerce_sqlite'
        );
        $app['config']->set('ecommerce.cache_duration', 60);
        $app['config']->set('ecommerce.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('ecommerce.redis_port', $defaultConfig['redis_port']);
        $app['config']->set('ecommerce.data_mode', $defaultConfig['data_mode']);
        $app['config']->set('ecommerce.entities', $defaultConfig['entities']);
        $app['config']->set('ecommerce.brand', $defaultConfig['brand']);
        $app['config']->set('ecommerce.available_brands', $defaultConfig['available_brands']);
        $app['config']->set('ecommerce.tax_rates_and_options', $defaultConfig['tax_rates_and_options']);
        $app['config']->set('ecommerce.paypal', $defaultConfig['payment_gateways']['paypal']);
        $app['config']->set('ecommerce.stripe', $defaultConfig['payment_gateways']['stripe']);
        $app['config']->set('ecommerce.payment_gateways', $defaultConfig['payment_gateways']);
        $app['config']->set('ecommerce.supported_currencies', $defaultConfig['supported_currencies']);
        $app['config']->set('ecommerce.default_currency', $defaultConfig['default_currency']);
        $app['config']->set(
            'ecommerce.default_currency_conversion_rates',
            $defaultConfig['default_currency_conversion_rates']
        );

        $app['config']->set('ecommerce.invoice_email_details', $defaultConfig['invoice_email_details']);

        $app['config']->set('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only',
            $defaultConfig['days_before_access_revoked_after_expiry_in_app_purchases_only']);
        $app['config']->set('ecommerce.days_before_access_revoked_after_expiry', $defaultConfig['days_before_access_revoked_after_expiry']);
        
        $app['config']->set('ecommerce.payment_plan_minimum_price', $defaultConfig['payment_plan_minimum_price']);
        $app['config']->set('ecommerce.payment_plan_options', $defaultConfig['payment_plan_options']);
        $app['config']->set('ecommerce.financing_cost_per_order', $defaultConfig['financing_cost_per_order']);
        $app['config']->set('ecommerce.type_product', $defaultConfig['type_product']);
        $app['config']->set('ecommerce.type_subscription', $defaultConfig['type_subscription']);
        $app['config']->set('ecommerce.type_payment_plan', $defaultConfig['type_payment_plan']);
        $app['config']->set('ecommerce.shipping_address', $defaultConfig['shipping_address']);
        $app['config']->set('ecommerce.billing_address', $defaultConfig['billing_address']);
        $app['config']->set('ecommerce.paypal_payment_method_type', $defaultConfig['paypal_payment_method_type']);
        $app['config']->set(
            'ecommerce.credit_cart_payment_method_type',
            $defaultConfig['credit_cart_payment_method_type']
        );
        $app['config']->set('ecommerce.manual_payment_method_type', $defaultConfig['manual_payment_method_type']);
        $app['config']->set('ecommerce.order_payment_type', $defaultConfig['order_payment_type']);
        $app['config']->set('ecommerce.renewal_payment_type', $defaultConfig['renewal_payment_type']);
        $app['config']->set('ecommerce.interval_type_daily', $defaultConfig['interval_type_daily']);
        $app['config']->set('ecommerce.interval_type_monthly', $defaultConfig['interval_type_monthly']);
        $app['config']->set('ecommerce.interval_type_yearly', $defaultConfig['interval_type_yearly']);
        $app['config']->set('ecommerce.fulfillment_status_pending', $defaultConfig['fulfillment_status_pending']);
        $app['config']->set('ecommerce.fulfillment_status_fulfilled', $defaultConfig['fulfillment_status_fulfilled']);

        $app['config']->set('ecommerce.paypal.agreement_route', $defaultConfig['paypal']['agreement_route']);
        $app['config']->set(
            'ecommerce.paypal.agreement_fulfilled_path',
            $defaultConfig['paypal']['agreement_fulfilled_path']
        );

        $app['config']->set('ecommerce.subscription_renewal_date', $defaultConfig['subscription_renewal_date']);
        $app['config']->set('ecommerce.subscriptions_renew_cycles', $defaultConfig['subscriptions_renew_cycles']);
        $app['config']->set(
            'ecommerce.failed_payments_before_de_activation',
            $defaultConfig['failed_payments_before_de_activation']
        );

        $app['config']->set('location.environment', $locationConfig['environment']);
        $app['config']->set('location.testing_ip', $locationConfig['testing_ip']);
        $app['config']->set('location.api', $locationConfig['api']);
        $app['config']->set('location.active_api', $locationConfig['active_api']);
        $app['config']->set('location.countries', $locationConfig['countries']);
        $app['config']->set('location.country_regions', $locationConfig['country_regions']);

        $app['config']->set('remotestorage.filesystems.disks', $remoteStorageConfig['filesystems.disks']);
        $app['config']->set('remotestorage.filesystems.default', $remoteStorageConfig['filesystems.default']);

        $app['config']->set('ecommerce.development_mode', $defaultConfig['development_mode'] ?? true);
        $app['config']->set('usora.development_mode', $defaultConfig['development_mode'] ?? true);
        $app['config']->set('ecommerce.database_driver', 'pdo_sqlite');
        $app['config']->set('ecommerce.database_user', 'root');
        $app['config']->set('ecommerce.database_password', 'root');
        $app['config']->set('ecommerce.database_in_memory', true);

        // if new packages entities are required for testing, their entity directory/namespace config should be merged here
        $railactionlogEntities = [
            [
                'path' => __DIR__ . '/../vendor/railroad/railactionlog/src/Entities',
                'namespace' => 'Railroad\ActionLog\Entities',
            ]
        ];
        $app['config']->set('doctrine.entities', array_merge(
            $defaultConfig['entities'],
            $railactionlogEntities
        ));
        $app['config']->set('doctrine.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('doctrine.redis_port', $defaultConfig['redis_port']);

        $app['config']->set('railactionlog.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('railactionlog.redis_port', $defaultConfig['redis_port']);

        // sqlite
        $app['config']->set('doctrine.development_mode', $defaultConfig['development_mode'] ?? true);
        $app['config']->set('doctrine.database_driver', 'pdo_sqlite');
        $app['config']->set('doctrine.database_user', 'root');
        $app['config']->set('doctrine.database_password', 'root');
        $app['config']->set('doctrine.database_in_memory', true);

        $app['config']->set('railactionlog.development_mode', $defaultConfig['development_mode'] ?? true);
        $app['config']->set('railactionlog.database_driver', 'pdo_sqlite');
        $app['config']->set('railactionlog.database_user', 'root');
        $app['config']->set('railactionlog.database_password', 'root');
        $app['config']->set('railactionlog.database_in_memory', true);
        $app['config']->set('railactionlog.entities', $railactionlogEntities);
        $app['config']->set('railactionlog.data_mode', 'host');

        $app['config']->set('ecommerce.database_connection_name', 'ecommerce_sqlite');
        $app['config']->set('database.default', 'ecommerce_sqlite');
        $app['config']->set(
            'database.connections.' . 'ecommerce_sqlite',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );

        $app['config']->set('ecommerce.membership_product_syncing_info', [
            'drumeo' => [
                'membership_product_skus' => [
                    'DRUMEO_MEMBERSHIP_RECURRING_YEARLY',
                    'DRUMEO_MEMBERSHIP_RECURRING_MONTHLY',
                    'DRUMEO_MEMBERSHIP_LIFETIME',
                ],
            ],
            'pianote]' => [
                'membership_product_skus' => [
                    'PIANOTE_MEMBERSHIP_RECURRING_YEARLY',
                    'PIANOTE_MEMBERSHIP_LIFETIME',
                ],
            ]
        ]);

        $app->register(DoctrineServiceProvider::class);

        // allows access to built in user auth
        $app['config']->set('auth.providers.users.model', User::class);

        // countries

        // register provider
        $app->register(EcommerceServiceProvider::class);
        $app->register(LocationServiceProvider::class);
        $app->register(RemoteStorageServiceProvider::class);
        $app->register(CountriesServiceProvider::class);
        $app->register(PermissionsServiceProvider::class);
        $app->register(ActionLogServiceProvider::class);

        $this->currencies = $defaultConfig['supported_currencies'];
        $this->defaultCurrency = $defaultConfig['default_currency'];
        $this->paymentGateway = $defaultConfig['payment_gateways'];
        $this->paymentPlanOptions = array_values(
            array_filter(
                $defaultConfig['payment_plan_options'],
                function ($value) {
                    return $value != 1;
                }
            )
        );

        $this->paymentPlanMinimumPrice = $defaultConfig['payment_plan_minimum_price'];
    }

    protected function createUsersTable()
    {
        if (!$this->app['db']->connection()
            ->getSchemaBuilder()
            ->hasTable('users')) {
            $this->app['db']->connection()
                ->getSchemaBuilder()
                ->create(
                    self::TABLES['users'],
                    function (Blueprint $table) {
                        $table->increments('id');
                        $table->string('email');
                        $table->string('password');
                        $table->string('display_name');
                        $table->timestamps();
                    }
                );
        }
    }

    /**
     * @param string $filenameAbsolute
     * @return string
     */
    protected function getFilenameRelativeFromAbsolute($filenameAbsolute)
    {
        $tempDirPath = sys_get_temp_dir() . '/';

        return str_replace($tempDirPath, '', $filenameAbsolute);
    }

    /**
     * @return int
     */
    public function createAndLogInNewUser($email = null)
    {
        if (!$email) {
            $email = $this->faker->email;
        }

        $userId =
            $this->databaseManager->table('users')
                ->insertGetId(
                    [
                        'email' => $email,
                        'password' => $this->faker->password,
                        'display_name' => $this->faker->name,
                        'created_at' => Carbon::now()
                            ->toDateTimeString(),
                        'updated_at' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );

        Auth::shouldReceive('check')
            ->andReturn(true);

        Auth::shouldReceive('id')
            ->andReturn($userId);

        $userMockResults = ['id' => $userId, 'email' => $email];
        Auth::shouldReceive('user')
            ->andReturn($userMockResults);

        return $userId;
    }

    /**
     * Helper method to seed a test user
     * this method does not log in the newly created user
     *
     * @return array
     */
    public function fakeUser($userData = [])
    {
        $userData += [
            'email' => $this->faker->email,
            'password' => $this->faker->password,
            'display_name' => $this->faker->name,
            'created_at' => Carbon::now()
                ->toDateTimeString(),
            'updated_at' => Carbon::now()
                ->toDateTimeString(),
        ];

        $userId =
            $this->databaseManager->table('users')
                ->insertGetId($userData);

        $userData['id'] = $userId;

        return $userData;
    }

    /**
     * Helper method to seed a test product
     *
     * @return array
     */
    public function fakeProduct($productStub = []): array
    {
        $product = $this->faker->product($productStub);

        $productId =
            $this->databaseManager->table(self::TABLES['products'])
                ->insertGetId($product);

        $product['id'] = $productId;

        return $product;
    }

    /**
     * Helper method to seed a test access code
     *
     * @return array
     */
    public function fakeAccessCode($accessCodeStub = []): array
    {
        $accessCode = $this->faker->accessCode($accessCodeStub);
        $accessCode['product_ids'] = serialize($accessCode['product_ids']);

        $accessCodeId =
            $this->databaseManager->table(self::TABLES['accessCodes'])
                ->insertGetId($accessCode);

        $accessCode['id'] = $accessCodeId;

        return $accessCode;
    }

    /**
     * Helper method to seed a test subscription
     *
     * @return array
     */
    public function fakeSubscription($subscriptionStub = []): array
    {
        $subscription = $this->faker->subscription($subscriptionStub);

        $subscriptionId =
            $this->databaseManager->table(self::TABLES['subscriptions'])
                ->insertGetId($subscription);

        $subscription['id'] = $subscriptionId;

        return $subscription;
    }

    /**
     * Helper method to seed a test address
     *
     * @return array
     */
    public function fakeAddress($addressStub = []): array
    {
        $address = $this->faker->address($addressStub);

        $addressId =
            $this->databaseManager->table(self::TABLES['addresses'])
                ->insertGetId($address);

        $address['id'] = $addressId;

        return $address;
    }


    /**
     * @param array $membershipActionStub
     * @return array
     */
    public function fakeMembershipAction($membershipActionStub = []): array
    {
        $membershipAction = $this->faker->membershipAction($membershipActionStub);

        $addressId =
            $this->databaseManager->table(self::TABLES['membershipActions'])
                ->insertGetId($membershipAction);

        $membershipAction['id'] = $addressId;

        return $membershipAction;
    }

    /**
     * Helper method to seed a test customer
     *
     * @return array
     */
    public function fakeCustomer($customerStub = []): array
    {
        $customer = $this->faker->customer($customerStub);

        $customerId =
            $this->databaseManager->table(self::TABLES['customers'])
                ->insertGetId($customer);

        $customer['id'] = $customerId;

        return $customer;
    }

    /**
     * Helper method to seed a test order item
     *
     * @return array
     */
    public function fakeOrderItem($orderItemStub = []): array
    {
        $orderItem = $this->faker->orderItem($orderItemStub);

        $orderItemId =
            $this->databaseManager->table(self::TABLES['orderItems'])
                ->insertGetId($orderItem);

        $orderItem['id'] = $orderItemId;

        return $orderItem;
    }

    /**
     * Helper method to seed a test discount
     *
     * @return array
     */
    public function fakeDiscount($discountStub = []): array
    {
        $discount = $this->faker->discount($discountStub);

        $discountId =
            $this->databaseManager->table(self::TABLES['discounts'])
                ->insertGetId($discount);

        $discount['id'] = $discountId;

        return $discount;
    }

    /**
     * Helper method to seed a test discount criteria
     *
     * @return array
     */
    public function fakeDiscountCriteria($discountCriteriaStub = []): array
    {
        $discountCriteria = $this->faker->discountCriteria($discountCriteriaStub);

        $discountCriteriaId =
            $this->databaseManager->table(self::TABLES['discountCriteria'])
                ->insertGetId($discountCriteria);

        $discountCriteria['id'] = $discountCriteriaId;

        return $discountCriteria;
    }

    /**
     * Helper method to seed a test shipping option
     *
     * @return array
     */
    public function fakeShippingOption($shippingOptionStub = []): array
    {
        $shippingOption = $this->faker->shippingOption($shippingOptionStub);

        $shippingOptionId =
            $this->databaseManager->table(self::TABLES['shippingOptions'])
                ->insertGetId($shippingOption);

        $shippingOption['id'] = $shippingOptionId;

        return $shippingOption;
    }

    /**
     * Helper method to seed a test shipping costs weight range
     *
     * @return array
     */
    public function fakeShippingCost($shippingCostStub = []): array
    {
        $shippingCost = $this->faker->shippingCost($shippingCostStub);

        $shippingCostId =
            $this->databaseManager->table(self::TABLES['shippingCosts'])
                ->insertGetId($shippingCost);

        $shippingCost['id'] = $shippingCostId;

        return $shippingCost;
    }

    /**
     * Helper method to seed a test payment method
     *
     * @return array
     */
    public function fakePaymentMethod($paymentMethodStub = []): array
    {
        $paymentMethod = $this->faker->paymentMethod($paymentMethodStub);

        $paymentMethodId =
            $this->databaseManager->table(self::TABLES['paymentMethods'])
                ->insertGetId($paymentMethod);

        $paymentMethod['id'] = $paymentMethodId;

        unset($paymentMethod['credit_card_id']);
        unset($paymentMethod['paypal_billing_agreement_id']);

        return $paymentMethod;
    }

    /**
     * Helper method to seed a test order
     *
     * @return array
     */
    public function fakeOrder($orderStub = []): array
    {
        $order = $this->faker->order($orderStub);

        $orderId =
            $this->databaseManager->table(self::TABLES['orders'])
                ->insertGetId($order);

        $order['id'] = $orderId;

        return $order;
    }

    /**
     * Helper method to seed a test credit card
     *
     * @return array
     */
    public function fakeCreditCard($creditCardStub = []): array
    {
        $creditCard = $this->faker->creditCard($creditCardStub);

        $creditCardId =
            $this->databaseManager->table(self::TABLES['creditCards'])
                ->insertGetId($creditCard);

        $creditCard['id'] = $creditCardId;

        return $creditCard;
    }

    /**
     * Helper method to seed a test user product
     *
     * @return array
     */
    public function fakeUserProduct($userProductStub = []): array
    {
        $userProduct = $this->faker->userProduct($userProductStub);

        $userProductId =
            $this->databaseManager->table(self::TABLES['userProducts'])
                ->insertGetId($userProduct);

        $userProduct['id'] = $userProductId;

        return $userProduct;
    }

    /**
     * Helper method to seed a test credit card
     *
     * @return array
     */
    public function fakePaypalBillingAgreement(
        $paypalBillingAgreementStub = []
    ): array
    {

        $paypalBillingAgreement = $this->faker->paypalBillingAgreement($paypalBillingAgreementStub);

        $paypalBillingAgreementId =
            $this->databaseManager->table(self::TABLES['paypalBillingAgreements'])
                ->insertGetId($paypalBillingAgreement);

        $paypalBillingAgreement['id'] = $paypalBillingAgreementId;

        return $paypalBillingAgreement;
    }

    /**
     * Helper method to seed a test credit card
     *
     * @return array
     */
    public function fakeUserPaymentMethod($userPaymentMethodStub = []): array
    {
        $userPaymentMethod = $this->faker->userPaymentMethod($userPaymentMethodStub);

        $userPaymentMethodId =
            $this->databaseManager->table(self::TABLES['userPaymentMethod'])
                ->insertGetId($userPaymentMethod);

        $userPaymentMethod['id'] = $userPaymentMethodId;

        return $userPaymentMethod;
    }

    /**
     * Helper method to seed a test payment
     *
     * @return array
     */
    public function fakePayment($paymentStub = []): array
    {
        $payment = $this->faker->payment($paymentStub);

        $paymentId =
            $this->databaseManager->table(self::TABLES['payments'])
                ->insertGetId($payment);

        $payment['id'] = $paymentId;

        return $payment;
    }

    /**
     * Helper method to seed a test order payment
     *
     * @return array
     */
    public function fakeOrderPayment($orderPaymentStub = []): array
    {
        $orderPayment = $orderPaymentStub + ['created_at' => Carbon::now()];

        $orderPaymentId =
            $this->databaseManager->table(self::TABLES['orderPayments'])
                ->insertGetId($orderPayment);

        $orderPayment['id'] = $orderPaymentId;

        return $orderPayment;
    }

    /**
     * Helper method to seed a test subscription payment
     *
     * @return array
     */
    public function fakeSubscriptionPayment($subscriptionPaymentStub = []): array
    {
        $subscriptionPayment = $subscriptionPaymentStub + ['created_at' => Carbon::now()];

        $subscriptionPaymentId =
            $this->databaseManager->table(self::TABLES['subscriptionPayments'])
                ->insertGetId($subscriptionPayment);

        $subscriptionPayment['id'] = $subscriptionPaymentId;

        return $subscriptionPayment;
    }

    /**
     * Helper method to seed a test subscription payment
     *
     * @return array
     */
    public function fakeOrderItemFulfillment(
        $orderItemFulfillmentStub = []
    ): array
    {

        $orderItemFulfillment = $this->faker->orderItemFulfillment($orderItemFulfillmentStub);

        $orderItemFulfillmentId =
            $this->databaseManager->table(self::TABLES['orderItemFulfillments'])
                ->insertGetId($orderItemFulfillment);

        $orderItemFulfillment['id'] = $orderItemFulfillmentId;

        return $orderItemFulfillment;
    }

    /**
     * Helper method to seed a test refund
     *
     * @return array
     */
    public function fakeRefund($refundStub = []): array
    {
        $refund = $refundStub + [
                'payment_id' => $this->faker->randomNumber(2, true),
                'payment_amount' => $this->faker->randomFloat(2, 1, 100),
                'refunded_amount' => $this->faker->randomFloat(2, 1, 100),
                'note' => $this->faker->word,
                'external_provider' => $this->faker->word,
                'external_id' => $this->faker->word,
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ];

        $refundId =
            $this->databaseManager->table(self::TABLES['refunds'])
                ->insertGetId($refund);

        $refund['id'] = $refundId;

        return $refund;
    }

    /**
     * Helper method to seed a test user stripe customer id record
     *
     * @return array
     */
    public function fakeUserStripeCustomerId($dataStub = []): array
    {
        $data = $dataStub + [
                'user_id' => $this->faker->randomNumber(2, true),
                'stripe_customer_id' => $this->faker->randomNumber(2, true),
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ];

        $newRecordId =
            $this->databaseManager->table(self::TABLES['userStripeCustomerId'])
                ->insertGetId($data);

        $data['id'] = $newRecordId;

        return $data;
    }

    /**
     * Helper method to seed a test order payment
     *
     * @return array
     */
    public function fakeDiscountCriteriaProduct($discountCriteriaProduct = []): array
    {
        $discountCriteriaProductId =
            $this->databaseManager->table(self::TABLES['discountCriteriasProducts'])
                ->insertGetId($discountCriteriaProduct);

        $discountCriteriaProduct['id'] = $discountCriteriaProductId;

        return $discountCriteriaProduct;
    }

    /**
     * Helper method to seed a test apple receipt record
     *
     * @return array
     */
    public function fakeAppleReceipt($dataStub = []): array
    {
        $data = $dataStub + [
                'receipt' => $this->faker->word,
                'request_type' => AppleReceipt::MOBILE_APP_REQUEST_TYPE,
                'brand' => config('ecommerce.brand'),
                'valid' => true,
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ];

        $newRecordId =
            $this->databaseManager->table(self::TABLES['appleReceipts'])
                ->insertGetId($data);

        $data['id'] = $newRecordId;

        return $data;
    }

    /**
     * Helper method to seed a test membership stats record
     *
     * @return array
     */
    public function fakeMembershipStats($dataStub = []): array
    {
        $data = $dataStub + [
                'new' => $this->faker->randomNumber(2, true),
                'active_state' => $this->faker->randomNumber(2, true),
                'expired' => $this->faker->randomNumber(2, true),
                'suspended_state' => $this->faker->randomNumber(2, true),
                'canceled' => $this->faker->randomNumber(2, true),
                'canceled_state' => $this->faker->randomNumber(2, true),
                'interval_type' => $this->faker->randomElement([
                    MembershipStats::TYPE_ONE_MONTH,
                    MembershipStats::TYPE_SIX_MONTHS,
                    MembershipStats::TYPE_ONE_YEAR,
                    MembershipStats::TYPE_LIFETIME,
                ]),
                'stats_date' => Carbon::now()->subDays($this->faker->randomNumber(2, true)),
                'brand' => $this->faker->word,
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ];

        $newRecordId =
            $this->databaseManager->table(self::TABLES['membershipStats'])
                ->insertGetId($data);

        $data['id'] = $newRecordId;

        return $data;
    }

    /**
     * Helper method to seed a test retention stats record
     *
     * @return array
     */
    public function fakeRetentionStats($dataStub = []): array
    {
        $data = $dataStub + [
                'subscription_type' => $this->faker->randomElement([
                    RetentionStats::TYPE_ONE_MONTH,
                    RetentionStats::TYPE_SIX_MONTHS,
                    RetentionStats::TYPE_ONE_YEAR
                ]),
                'interval_start_date' => Carbon::now()->subDays($this->faker->randomNumber(2, true)),
                'interval_end_date' => Carbon::now()->subDays($this->faker->randomNumber(2, true)),
                'brand' => config('ecommerce.brand'),
                'customers_start' => $this->faker->randomNumber(2, true),
                'customers_end' => $this->faker->randomNumber(2, true),
                'customers_new' => $this->faker->randomNumber(2, true),
                'created_at' => Carbon::now(),
                'updated_at' => null,
            ];

        $newRecordId =
            $this->databaseManager->table(self::TABLES['retentionStats'])
                ->insertGetId($data);

        $data['id'] = $newRecordId;

        return $data;
    }

    public function getCurrency()
    {
        return $this->faker->randomElement($this->currencies);
    }

    public function getPaymentGateway($processor)
    {
        return $this->faker->randomElement(array_keys($this->paymentGateway[$processor]));
    }

    public function getPaymentPlanOption()
    {
        return $this->faker->randomElement($this->paymentPlanOptions);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    protected function assertIncludes(array $expectedIncludes, array $actualIncludes)
    {
        foreach ($expectedIncludes as $expectedInclude) {
            $actualIncludesClone = $actualIncludes;

            // remove all ids that dont match
            foreach ($actualIncludes as $actualIncludeIndex => $actualInclude) {

                if (isset($expectedInclude['id']) && $expectedInclude['id'] != $actualInclude['id']) {
                    unset($actualIncludesClone[$actualIncludeIndex]);
                }
            }

            // remove types that dont match
            foreach ($actualIncludes as $actualIncludeIndex => $actualInclude) {

                if (isset($expectedInclude['type']) && $expectedInclude['type'] != $actualInclude['type']) {
                    unset($actualIncludesClone[$actualIncludeIndex]);
                }
            }

            // remove where attributes dont contain
            foreach ($actualIncludes as $actualIncludeIndex => $actualInclude) {

                // first make sure all the keys exist
                if (isset($expectedInclude['attributes']) &&
                    array_intersect_key($expectedInclude['attributes'], $actualInclude['attributes'] ?? []) !=
                    $expectedInclude['attributes']) {

                    unset($actualIncludesClone[$actualIncludeIndex]);
                    continue;
                }

                // make sure each individual attribute has a match
                if (isset($expectedInclude['attributes']) && !empty($expectedInclude['attributes'])) {
                    $attributeFound = false;

                    foreach ($expectedInclude['attributes'] as $expectedAttribute) {
                        foreach ($actualInclude['attributes'] ?? [] as $actualAttribute) {
                            if ($expectedAttribute == $actualAttribute) {
                                $attributeFound = true;
                            }
                        }
                    }

                    if (!$attributeFound) {
                        unset($actualIncludesClone[$actualIncludeIndex]);
                    }
                }

            }

            // remove where relationships dont match
            foreach ($actualIncludes as $actualIncludeIndex => $actualInclude) {

                if (isset($expectedInclude['relationships']) && !empty($expectedInclude['relationships'])) {
                    $relationshipFound = false;

                    foreach ($expectedInclude['relationships'] as $expectedRelationship) {
                        foreach ($actualInclude['relationships'] ?? [] as $actualRelationship) {
                            if ($expectedRelationship == $actualRelationship) {
                                $relationshipFound = true;
                            }
                        }
                    }

                    if (!$relationshipFound) {
                        unset($actualIncludesClone[$actualIncludeIndex]);
                    }
                }

            }

            $this->assertGreaterThan(
                0,
                count($actualIncludesClone),
                "Could not find include:\n" .
                print_r($expectedInclude, true) .
                "\nin: \n\n" .
                print_r($actualIncludes, true)
            );
        }
    }
}