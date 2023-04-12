<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreasePaymentTaxesDecimalPlaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->decimal('product_rate', 8, 5)->change();
            $table->decimal('shipping_rate', 8, 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->decimal('product_rate', 8, 2)->change();
            $table->decimal('shipping_rate', 8, 2)->change();
        });
    }
}
