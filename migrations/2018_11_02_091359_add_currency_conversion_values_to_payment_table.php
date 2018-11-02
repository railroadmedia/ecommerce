<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class AddCurrencyConversionValuesToPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tablePayment,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->decimal('paid_in_currency')->after('paid')->default(0)->index();
            }
        );

        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableSubscription,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->dropColumn('currency');
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
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tablePayment,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->dropColumn('paid_in_currency');
            }
        );

        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableSubscription,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->string('currency', 3)->after('shipping_per_payment')->index();
            }
        );
    }
}
