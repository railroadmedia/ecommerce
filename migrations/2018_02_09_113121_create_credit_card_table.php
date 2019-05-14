<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_credit_card',
            function(Blueprint $table) {
                $table->increments('id');
                $table->string('fingerprint');
                $table->integer('last_four_digits');
                $table->string('cardholder_name')->nullable();
                $table->string('company_name')->index();
                $table->dateTime('expiration_date');
                $table->string('external_id',64)->index();
                $table->string('external_customer_id',64)->index()->nullable();
                $table->string('payment_gateway_name', 64)->index();
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
        Schema::dropIfExists('ecommerce_credit_card');
    }
}
