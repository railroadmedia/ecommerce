<?php

namespace Railroad\Ecommerce\Tests;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Faker\Generator;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Faker\Factory;
use Railroad\Ecommerce\Providers\EcommerceServiceProvider;
use Railroad\Ecommerce\Providers\UserProviderInterface;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Tests\Resources\Models\User;
use Railroad\Location\Providers\LocationServiceProvider;
use Railroad\Permissions\Providers\PermissionsServiceProvider;
use Railroad\Permissions\Services\PermissionService;
use Railroad\RemoteStorage\Providers\RemoteStorageServiceProvider;
use Railroad\Response\Providers\ResponseServiceProvider;
use Railroad\Usora\Providers\UsoraServiceProvider;
use Railroad\Doctrine\Providers\DoctrineServiceProvider;
use Webpatser\Countries\CountriesServiceProvider;

class EcommerceTestCase extends BaseTestCase
{
    const TABLES = [
        'products' => 'ecommerce_product',
        'accessCodes' => 'ecommerce_access_code',
        'subscriptions' => 'ecommerce_subscription',
        'addresses' => 'ecommerce_address',
        'customers' => 'ecommerce_customer'
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
     * @var Application
     */
    protected $app;

    protected function setUp()
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);

        // Run the schema update tool using our entity metadata
        $this->entityManager = app(EntityManager::class);

        $this->entityManager->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        // make sure laravel is using the same connection
        DB::connection()->setPdo($this->entityManager->getConnection()->getWrappedConnection());
        DB::connection()->setReadPdo($this->entityManager->getConnection()->getWrappedConnection());

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

        Carbon::setTestNow(Carbon::now());

        // $this->artisan('countries:migration');
        $this->artisan('migrate');
        $this->artisan('cache:clear');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // setup package config for testing
        $defaultConfig = require(__DIR__ . '/../config/ecommerce.php');
        $locationConfig = require(__DIR__ . '/../vendor/railroad/location/config/location.php');
        $remoteStorageConfig = require(__DIR__ . '/../vendor/railroad/remotestorage/config/remotestorage.php');
        $usoraConfig = require(__DIR__ . '/../vendor/railroad/usora/config/usora.php');

        $app['config']->set('ecommerce.database_connection_name', 'testbench');
        $app['config']->set('ecommerce.cache_duration', 60);
        $app['config']->set('ecommerce.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('ecommerce.redis_port', $defaultConfig['redis_port']);
        $app['config']->set('ecommerce.table_prefix', $defaultConfig['table_prefix']);
        $app['config']->set('ecommerce.data_mode', $defaultConfig['data_mode']);
        $app['config']->set('ecommerce.brand', $defaultConfig['brand']);
        $app['config']->set('ecommerce.available_brands', $defaultConfig['available_brands']);
        $app['config']->set('ecommerce.tax_rate', $defaultConfig['tax_rate']);
        $app['config']->set('ecommerce.paypal', $defaultConfig['payment_gateways']['paypal']);
        $app['config']->set('ecommerce.stripe', $defaultConfig['payment_gateways']['stripe']);
        $app['config']->set('ecommerce.payment_gateways', $defaultConfig['payment_gateways']);
        $app['config']->set('ecommerce.supported_currencies', $defaultConfig['supported_currencies']);
        $app['config']->set('ecommerce.invoiceSender', $defaultConfig['invoiceSender']);
        $app['config']->set('ecommerce.invoiceAddress', $defaultConfig['invoiceAddress']);
        $app['config']->set('ecommerce.invoiceEmailSubject', $defaultConfig['invoiceEmailSubject']);
        $app['config']->set('ecommerce.paymentPlanMinimumPrice', $defaultConfig['paymentPlanMinimumPrice']);
        $app['config']->set('ecommerce.paymentPlanOptions', $defaultConfig['paymentPlanOptions']);
        $app['config']->set('ecommerce.typeProduct', $defaultConfig['typeProduct']);
        $app['config']->set('ecommerce.typeSubscription', $defaultConfig['typeSubscription']);
        $app['config']->set('ecommerce.typePaymentPlan', $defaultConfig['typePaymentPlan']);
        $app['config']->set('ecommerce.shippingAddress', $defaultConfig['shippingAddress']);
        $app['config']->set('ecommerce.billingAddress', $defaultConfig['billingAddress']);
        $app['config']->set('ecommerce.paypalPaymentMethodType', $defaultConfig['paypalPaymentMethodType']);
        $app['config']->set('ecommerce.creditCartPaymentMethodType', $defaultConfig['creditCartPaymentMethodType']);
        $app['config']->set('ecommerce.manualPaymentMethodType', $defaultConfig['manualPaymentMethodType']);
        $app['config']->set('ecommerce.orderPaymentType', $defaultConfig['orderPaymentType']);
        $app['config']->set('ecommerce.renewalPaymentType', $defaultConfig['renewalPaymentType']);
        $app['config']->set('ecommerce.intervalTypeDaily', $defaultConfig['intervalTypeDaily']);
        $app['config']->set('ecommerce.intervalTypeMonthly', $defaultConfig['intervalTypeMonthly']);
        $app['config']->set('ecommerce.intervalTypeYearly', $defaultConfig['intervalTypeYearly']);
        $app['config']->set('ecommerce.fulfillmentStatusPending', $defaultConfig['fulfillmentStatusPending']);
        $app['config']->set('ecommerce.fulfillmentStatusFulfilled', $defaultConfig['fulfillmentStatusFulfilled']);

        $app['config']->set('ecommerce.paypal.agreementRoute', $defaultConfig['paypal']['agreementRoute']);
        $app['config']->set(
            'ecommerce.paypal.agreementFulfilledRoute',
            $defaultConfig['paypal']['agreementFulfilledRoute']
        );

        $app['config']->set('ecommerce.subscription_renewal_date', $defaultConfig['subscription_renewal_date']);
        $app['config']->set('ecommerce.failed_payments_before_de_activation', $defaultConfig['failed_payments_before_de_activation']);

        $app['config']->set('location.environment', $locationConfig['environment']);
        $app['config']->set('location.testing_ip', $locationConfig['testing_ip']);
        $app['config']->set('location.api', $locationConfig['api']);
        $app['config']->set('location.active_api', $locationConfig['active_api']);
        $app['config']->set('location.countries', $locationConfig['countries']);

        $app['config']->set('remotestorage.filesystems.disks', $remoteStorageConfig['filesystems.disks']);
        $app['config']->set('remotestorage.filesystems.default', $remoteStorageConfig['filesystems.default']);
        $app['config']->set('usora.data_mode', $usoraConfig['data_mode']);
        $app['config']->set('usora.tables', $usoraConfig['tables']);

        $app['config']->set('usora.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('usora.redis_port', $defaultConfig['redis_port']);

        // if new packages entities are required for testing, their entity directory/namespace config should be merged here
        $app['config']->set(
            'doctrine.entities',
            array_merge(
                $defaultConfig['entities'],
                $usoraConfig['entities']
            )
        );
        $app['config']->set('doctrine.redis_host', $defaultConfig['redis_host']);
        $app['config']->set('doctrine.redis_port', $defaultConfig['redis_port']);

        // sqlite
        $app['config']->set('doctrine.development_mode', $defaultConfig['development_mode'] ?? true);
        $app['config']->set('doctrine.database_driver', 'pdo_sqlite');
        $app['config']->set('doctrine.database_user', 'root');
        $app['config']->set('doctrine.database_password', 'root');
        $app['config']->set('doctrine.database_in_memory', true);

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
        $app->register(UsoraServiceProvider::class);

        $app->bind(
            'UserProviderInterface',
            function () {
                $mock =
                    $this->getMockBuilder('UserProviderInterface')
                        ->setMethods(['create'])
                        ->getMock();

                $mock->method('create')
                    ->willReturn(
                        [
                            'id' => 1,
                            'email' => $this->faker->email,
                        ]
                    );
                return $mock;
            }
        );
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
    public function createAndLogInNewUser()
    {
        $email = $this->faker->email;
        $userId =
            $this->databaseManager
                ->table('usora_users')
                ->insertGetId(
                    [
                        'email' => $email,
                        'password' => $this->faker->password,
                        'display_name' => $this->faker->name,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
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
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];

        $userId = $this->databaseManager
            ->table('usora_users')
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

        $productId = $this->databaseManager
            ->table(self::TABLES['products'])
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

        $accessCodeId = $this->databaseManager
            ->table(self::TABLES['accessCodes'])
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

        $subscriptionId = $this->databaseManager
            ->table(self::TABLES['subscriptions'])
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

        $addressId = $this->databaseManager
            ->table(self::TABLES['addresses'])
            ->insertGetId($address);

        $address['id'] = $addressId;

        return $address;
    }

    /**
     * Helper method to seed a test customer
     *
     * @return array
     */
    public function fakeCustomer($customerStub = []): array
    {
        $customer = $this->faker->customer($customerStub);

        $customerId = $this->databaseManager
            ->table(self::TABLES['customers'])
            ->insertGetId($customer);

        $customer['id'] = $customerId;

        return $customer;
    }

    protected function tearDown()
    {
        parent::tearDown();
    }
}