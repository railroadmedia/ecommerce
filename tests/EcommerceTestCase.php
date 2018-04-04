<?php

namespace Railroad\Ecommerce\Tests;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Railroad\Ecommerce\Factories\AccessFactory;
use Railroad\Ecommerce\Factories\UserAccessFactory;
use Railroad\Ecommerce\Providers\EcommerceServiceProvider;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\RepositoryBase;
use Railroad\Ecommerce\Tests\Resources\Models\User;
use Railroad\Location\Providers\LocationServiceProvider;
use Railroad\Permissions\Providers\PermissionsServiceProvider;
use Railroad\RemoteStorage\Providers\RemoteStorageServiceProvider;
use Webpatser\Countries\CountriesServiceProvider;


class EcommerceTestCase extends BaseTestCase
{

    /**
     * @var Generator
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
     * @var AccessFactory
     */
    protected $accessFactory;

    /**
     * @var UserAccessFactory
     */
    protected $userAccessFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('countries:migration');
        $this->artisan('migrate');
        $this->artisan('cache:clear');

        $this->faker = $this->app->make(Generator::class);
        $this->databaseManager = $this->app->make(DatabaseManager::class);
        $this->authManager = $this->app->make(AuthManager::class);
        $this->accessFactory = $this->app->make(AccessFactory::class);
        $this->userAccessFactory = $this->app->make(UserAccessFactory::class);

        RepositoryBase::$connectionMask = null;

        Carbon::setTestNow(Carbon::now());
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
        $permissionTableConfig = require(__DIR__ . '/../config/permissions.php');
        $locationConfig = require(__DIR__ . '/../vendor/railroad/location/config/location.php');
        $remoteStorageConfig = require(__DIR__ . '/../vendor/railroad/remotestorage/config/remotestorage.php');
        $permissionConfig = require(__DIR__ . '/../vendor/railroad/permissions/config/permissions.php');

        $app['config']->set('ecommerce.database_connection_name', 'testbench');
        $app['config']->set('ecommerce.cache_duration', 60);
        $app['config']->set('ecommerce.table_prefix', $defaultConfig['table_prefix']);
        $app['config']->set('ecommerce.data_mode', $defaultConfig['data_mode']);
        $app['config']->set('ecommerce.brand', $defaultConfig['brand']);
        $app['config']->set('ecommerce.tax_rate', $defaultConfig['tax_rate']);
        $app['config']->set('ecommerce.credit_card', $defaultConfig['credit_card']);
        $app['config']->set('table_names', $permissionTableConfig['table_names']);
        $app['config']->set('column_names', $permissionTableConfig['column_names']);
        $app['config']->set('additional_join_for_owner', $permissionTableConfig['additional_join_for_owner']);

        $app['config']->set('location.environment', $locationConfig['environment']);
        $app['config']->set('location.testing_ip', $locationConfig['testing_ip']);
        $app['config']->set('location.api', $locationConfig['api']);
        $app['config']->set('location.active_api', $locationConfig['active_api']);

        $app['config']->set('remotestorage.filesystems.disks', $remoteStorageConfig['filesystems.disks']);
        $app['config']->set('remotestorage.filesystems.default', $remoteStorageConfig['filesystems.default']);

        $app['config']->set('permission.database_connection_name', $permissionConfig['database_connection_name']);
        $app['config']->set('permission.connection_mask_prefix', $permissionConfig['connection_mask_prefix']);

        // setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.mysql',
            [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => env('MYSQL_PORT', '3306'),
                'database' => env('MYSQL_DB','ecommerce'),
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'prefix' => '',
                'options' => [
                    \PDO::ATTR_PERSISTENT => true,
                ]
            ]
        );

        $app['config']->set(
            'database.connections.testbench',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]
        );

        // allows access to built in user auth
        $app['config']->set('auth.providers.users.model', User::class);

        // allows access to built in user auth
        $app['config']->set('auth.providers.users.model', User::class);


        if (!$app['db']->connection()->getSchemaBuilder()->hasTable('users')) {

            $app['db']->connection()->getSchemaBuilder()->create(
                'users',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('email');
                }
            );
        }

        // register provider
        $app->register(EcommerceServiceProvider::class);
        $app->register(LocationServiceProvider::class);
        $app->register(RemoteStorageServiceProvider::class);
        $app->register(CountriesServiceProvider::class);
        $app->register(PermissionsServiceProvider::class);
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
        $userId = $this->databaseManager->connection()->query()->from('users')->insertGetId(
            ['email' => $this->faker->email]
        );

        $this->authManager->guard()->onceUsingId($userId);

        request()->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        return $userId;
    }

    public function createAndLoginAdminUser()
    {
        $userId = $this->createAndLogInNewUser();

        $adminRole = $this->accessFactory->store(
            'admin','admin', ''
        );
        $admin = $this->userAccessFactory->assignAccessToUser($adminRole['id'], $userId);

        return $userId;

    }

    protected function tearDown()
    {
        parent::tearDown();
    }
}