<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class RenameDatetimeColumns extends Migration
{
    private $tables = [
        'ecommerce_access_code',
        'ecommerce_address',
        'ecommerce_credit_card',
        'ecommerce_customer',
        'ecommerce_customer_payment_methods',
        'ecommerce_discount',
        'ecommerce_discount_criteria',
        'ecommerce_order',
        'ecommerce_order_discount',
        'ecommerce_order_item',
        'ecommerce_order_item_fulfillment',
        'ecommerce_order_payment',
        'ecommerce_payment',
        'ecommerce_payment_method',
        'ecommerce_paypal_billing_agreement',
        'ecommerce_product',
        'ecommerce_refund',
        'ecommerce_shipping_costs_weight_range',
        'ecommerce_shipping_option',
        'ecommerce_subscription',
        'ecommerce_subscription_access_code',
        'ecommerce_subscription_payment',
        'ecommerce_user_payment_methods',
        'ecommerce_user_product',
    ];

    private $deletedOnTables = [
        'ecommerce_order',
        'ecommerce_payment',
        'ecommerce_payment_method',
        'ecommerce_subscription',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $_table) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($_table, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('created_on', 'created_at');
                });

            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($_table, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('updated_on', 'updated_at');
                });
        }

        foreach ($this->deletedOnTables as $deletedOnTable) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($deletedOnTable, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('deleted_on', 'deleted_at');
                });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $_table) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($_table, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('created_at', 'created_on');
                });

            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($_table, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('updated_at', 'updated_on');
                });
        }

        foreach ($this->deletedOnTables as $deletedOnTable) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table($deletedOnTable, function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('deleted_at', 'deleted_on');
                });
        }
    }
}


