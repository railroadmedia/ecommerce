<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreatePaypalBillingAgreementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tablePaypalBillingAgreement,
            function(Blueprint $table) {
                $table->increments('id');
                $table->string('agreement_id',64)->index();

                $table->string('express_checkout_token', 64)->index();
                $table->integer('address_id');
                $table->string('payment_gateway_name', 64)->index();
                $table->dateTime('expiration_date')->index();
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
        Schema::dropIfExists(ConfigService::$tableShippingCostsWeightRange);
    }
}
