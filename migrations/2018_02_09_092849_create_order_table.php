<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableOrder,
            function(Blueprint $table) {
                $table->increments('id');

                $table->string('uuid')->unique();
                $table->decimal('due');
                $table->decimal('tax');
                $table->decimal('shipping_costs');
                $table->decimal('paid');
                $table->integer('user_id')->index()->nullable();
                $table->integer('customer_id')->index()->nullable();
                $table->string('brand')->index();
                $table->integer('shipping_address_id')->nullable();
                $table->integer('billing_address_id')->nullable();
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
        Schema::dropIfExists(ConfigService::$tableOrder);
    }
}
