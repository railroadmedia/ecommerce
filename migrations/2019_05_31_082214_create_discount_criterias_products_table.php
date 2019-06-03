<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountCriteriasProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))
            ->create(
                'ecommerce_discount_criterias_products',
                function(Blueprint $table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->integer('discount_criteria_id')->index();
                    $table->integer('product_id')->index();
                    $table->primary(['discount_criteria_id', 'product_id']);
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
        Schema::dropIfExists('ecommerce_discount_criterias_products');
    }
}
