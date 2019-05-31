<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDiscountCriteriaProductsRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_discount_criteria',
                function ($table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->dropIndex('ecommerce_discount_criteria_product_id_index');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_discount_criteria',
                function ($table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->dropColumn('product_id');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_discount_criteria',
                function (Blueprint $table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->string('products_relation_type')->nullable()->after('type');
                }
            );

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
        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_discount_criteria',
                function ($table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->dropColumn('products_relation_type');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_discount_criteria',
                function ($table) {
                    /** @var $table \Illuminate\Database\Schema\Blueprint */
                    $table->integer('product_id')->index()->nullable();
                }
            );

        Schema::dropIfExists('ecommerce_discount_criterias_products');
    }
}
