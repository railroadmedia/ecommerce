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
            ConfigService::$tablePaymentMethod,
            function(Blueprint $table) {
                $table->increments('id');
                $table->string('type')->index();
                $table->integer('credit_card_id')->nullable();
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
        Schema::dropIfExists(ConfigService::$tablePaymentMethod);
    }
}
