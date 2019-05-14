<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_payment',
            function(Blueprint $table) {
                $table->increments('id');

                $table->decimal('due');
                $table->decimal('paid')->nullable();
                $table->decimal('refunded')->nullable();
                $table->string('type')->index();
                $table->string('external_id', 64)->index()->nullable();
                $table->string('external_provider', 64)->index()->nullable();
                $table->string('status', 64)->index();
                $table->text('message')->nullable();
                $table->integer('payment_method_id')->index()->nullable();
                $table->string('currency', 3)->index();
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
        Schema::dropIfExists('ecommerce_payment');
    }
}
