<?php

namespace Railroad\Ecommerce\Providers;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PDO;
use Railroad\Ecommerce\Services\ConfigService;


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

                    if ($event->connection->getName() ==
                        ConfigService::$connectionMaskPrefix . ConfigService::$databaseConnectionName) {
                        $event->statement->setFetchMode(PDO::FETCH_ASSOC);
                    }
                }
            ],
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

        //load package routes file
        $this->loadRoutesFrom(__DIR__ . '/../../routes/routes.php');
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
        ConfigService::$tableShippingOption= ConfigService::$tablePrefix . 'shipping_option';
        ConfigService::$tableShippingCostsWeightRange = ConfigService::$tablePrefix . 'shipping_costs_weight_range';
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