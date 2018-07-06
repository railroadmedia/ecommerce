<?php

namespace Railroad\Ecommerce\Tests;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as BaseTestCase;
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
use Webpatser\Countries\CountriesServiceProvider;

class EcommerceTestCase extends BaseTestCase
{
    /**
     * @var \Railroad\Ecommerce\Faker\Faker
     */
    protected $faker;

    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

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

        $this->faker           = Factory::create();
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager     = $this->app->make(AuthManager::class);

        $this->permissionServiceMock = $this->getMockBuilder(PermissionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance(PermissionService::class, $this->permissionServiceMock);

        $this->stripeExternalHelperMock = $this->getMockBuilder(\Railroad\Ecommerce\ExternalHelpers\Stripe::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance(\Railroad\Ecommerce\ExternalHelpers\Stripe::class, $this->stripeExternalHelperMock);

        $this->paypalExternalHelperMock = $this->getMockBuilder(\Railroad\Ecommerce\ExternalHelpers\PayPal::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance(\Railroad\Ecommerce\ExternalHelpers\PayPal::class, $this->paypalExternalHelperMock);

        Carbon::setTestNow(Carbon::now());

        $this->artisan('countries:migration');
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
        $defaultConfig       = require(__DIR__ . '/../config/ecommerce.php');
        $locationConfig      = require(__DIR__ . '/../vendor/railroad/location/config/location.php');
        $remoteStorageConfig = require(__DIR__ . '/../vendor/railroad/remotestorage/config/remotestorage.php');

        $app['config']->set('ecommerce.database_connection_name', 'testbench');
        $app['config']->set('ecommerce.cache_duration', 60);
        $app['config']->set('ecommerce.table_prefix', $defaultConfig['table_prefix']);
        $app['config']->set('ecommerce.data_mode', $defaultConfig['data_mode']);
        $app['config']->set('ecommerce.brand', $defaultConfig['brand']);
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

        $app['config']->set('location.environment', $locationConfig['environment']);
        $app['config']->set('location.testing_ip', $locationConfig['testing_ip']);
        $app['config']->set('location.api', $locationConfig['api']);
        $app['config']->set('location.active_api', $locationConfig['active_api']);
        $app['config']->set('location.countries', $locationConfig['countries']);

        $app['config']->set('remotestorage.filesystems.disks', $remoteStorageConfig['filesystems.disks']);
        $app['config']->set('remotestorage.filesystems.default', $remoteStorageConfig['filesystems.default']);

        // setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.mysql',
            [
                'driver'    => 'mysql',
                'host'      => 'mysql',
                'port'      => env('MYSQL_PORT', '3306'),
                'database'  => env('MYSQL_DB', 'ecommerce'),
                'username'  => 'root',
                'password'  => 'root',
                'charset'   => 'utf8',
                'collation' => 'utf8_general_ci',
                'prefix'    => '',
                'options'   => [
                    \PDO::ATTR_PERSISTENT => true,
                ]
            ]
        );

        $app['config']->set(
            'database.connections.testbench',
            [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]
        );

        // allows access to built in user auth
        $app['config']->set('auth.providers.users.model', User::class);

        // allows access to built in user auth
        $app['config']->set('auth.providers.users.model', User::class);

        if(!$app['db']->connection()->getSchemaBuilder()->hasTable('users'))
        {

            $app['db']->connection()->getSchemaBuilder()->create(
                'users',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('email');
                }
            );
        }

        // countries

        // register provider
        $app->register(EcommerceServiceProvider::class);
        $app->register(LocationServiceProvider::class);
        $app->register(RemoteStorageServiceProvider::class);
        $app->register(CountriesServiceProvider::class);
        $app->register(PermissionsServiceProvider::class);
        $app->register(ResponseServiceProvider::class);

        $app->bind('UserProviderInterface', function () {
            $mock = $this->getMockBuilder('UserProviderInterface')
                ->setMethods(['create'])
                ->getMock();

            $mock->method('create')->willReturn([
                'id'    => 1,
                'email' => $this->faker->email
            ]);
            return $mock;
        });
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
        $email  = $this->faker->email;
        $userId = $this->databaseManager->connection()->query()->from('users')->insertGetId(
            ['email' => $email]
        );

        Auth::shouldReceive('id')->andReturn($userId);

        $userMockResults = ['id' => $userId, 'email' => $email];
        Auth::shouldReceive('user')->andReturn($userMockResults);

        return $userId;
    }


    protected function tearDown()
    {
        parent::tearDown();
    }
}