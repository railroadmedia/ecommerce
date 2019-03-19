<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateShippingCostsWeightRangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            'ecommerce_shipping_costs_weight_range',
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('shipping_option_id')->index();
                $table->decimal('min');
                $table->decimal('max');
                $table->decimal('price');
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
        Schema::dropIfExists('ecommerce_shipping_costs_weight_range');
    }
}
