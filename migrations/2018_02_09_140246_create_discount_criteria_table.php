<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountCriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_discount_criteria',
            function(Blueprint $table) {
                $table->increments('id');
                $table->string('name')->index();
                $table->string('type')->index();
                $table->integer('product_id')->index()->nullable();
                $table->string('min');
                $table->string('max');
                $table->integer('discount_id')->index();
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
        Schema::dropIfExists('ecommerce_discount_criteria');
    }
}
