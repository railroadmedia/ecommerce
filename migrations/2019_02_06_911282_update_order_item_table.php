<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class UpdateOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('weight')
                        ->after('quantity')
                        ->nullable();
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('discount', 'total_discounted');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('tax');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('shipping_costs');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_price', 'final_price');
                }
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('weight');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_discounted', 'discount');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('tax')
                        ->after('discount');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('shipping_costs')
                        ->after('initial_price');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrderItem,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('final_price', 'total_price');
                }
            );
    }
}
