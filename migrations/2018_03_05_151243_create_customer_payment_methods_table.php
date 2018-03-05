<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateCustomerPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableCustomerPaymentMethods,
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('customer_id')->index();
                $table->integer('payment_method_id')->index();
                $table->boolean('is_primary')->default(false);
                $table->dateTime('created_on')->index();
                $table->dateTime('updated_on')->index()->nullable();
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
        Schema::dropIfExists(ConfigService::$tableCustomerPaymentMethods);
    }
}
