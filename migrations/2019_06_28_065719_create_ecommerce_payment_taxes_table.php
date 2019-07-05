<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEcommercePaymentTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecommerce_payment_taxes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_id')->index();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->decimal('product_rate')->nullable();
            $table->decimal('shipping_rate')->nullable();
            $table->decimal('product_taxes_paid')->nullable();
            $table->decimal('shipping_taxes_paid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ecommerce_payment_taxes');
    }
}
