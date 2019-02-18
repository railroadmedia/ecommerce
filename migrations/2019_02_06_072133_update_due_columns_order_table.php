<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class UpdateDueColumnsOrderTable extends Migration
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
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('due', 'total_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('product_due')
                        ->after('total_due')
                        ->index()
                        ->nullable();
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('tax', 'taxes_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('shipping_costs', 'shipping_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('finance_due')
                        ->after('shipping_due')
                        ->index()
                        ->nullable();
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('paid', 'total_paid');
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
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_due', 'due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('product_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('tax_due', 'tax');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('shipping_due', 'shipping_costs');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('finance_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tableOrder,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_paid', 'paid');
                }
            );
    }
}
