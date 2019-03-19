<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreatePaymentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            'ecommerce_payment_method',
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('method_id')->index();
                $table->string('method_type')->index();
                $table->string('currency', 3)->index();
                $table->integer('billing_address_id')->nullable();
                $table->dateTime('created_on')->index();
                $table->dateTime('updated_on')->index()->nullable();
                $table->dateTime('deleted_on')->index()->nullable();
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
        Schema::dropIfExists('ecommerce_payment_method');
    }
}
