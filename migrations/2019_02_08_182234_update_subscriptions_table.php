<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class UpdateSubscriptionsTable extends Migration
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
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_price_per_payment', 'total_price');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('tax_per_payment');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('shipping_per_payment');
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
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_price', 'total_price_per_payment');
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->decimal('tax_per_payment')->nullable();
                }
            );

        Schema::connection(ConfigService::$databaseConnectionName)
            ->table(
                'ecommerce_subscription',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->decimal('shipping_per_payment')->nullable();
                }
            );
    }
}
