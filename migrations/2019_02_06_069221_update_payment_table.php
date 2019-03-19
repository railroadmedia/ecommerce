<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class UpdatePaymentTable extends Migration
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
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('due', 'total_due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('paid', 'total_paid');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('refunded', 'total_refunded');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('conversion_rate', 8, 2)
                        ->after('total_refunded')
                        ->nullable();
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
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_due', 'due');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_paid', 'paid');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_refunded', 'refunded');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                ConfigService::$tablePayment,
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('conversion_rate');
                }
            );
    }
}
