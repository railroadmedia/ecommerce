<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateOrderItemFulfillmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableOrderItemFulfillment,
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('order_id')->index();
                $table->integer('order_item_id')->index();
                $table->string('status', 64)->index();
                $table->string('company')->nullable();
                $table->string('tracking_number')->nullable();
                $table->dateTime('fulfilled_on')->nullable();
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
        Schema::dropIfExists(ConfigService::$tableOrderItemFulfillment);
    }
}
