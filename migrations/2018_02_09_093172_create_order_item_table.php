<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            'ecommerce_order_item',
            function(Blueprint $table) {
                $table->increments('id');

                $table->integer('order_id')->index();
                $table->integer('product_id')->index();
                $table->integer('quantity');
                $table->decimal('initial_price');
                $table->decimal('discount');
                $table->decimal('tax');
                $table->decimal('shipping_costs');
                $table->decimal('total_price');
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
        Schema::dropIfExists('ecommerce_order_item');
    }
}
