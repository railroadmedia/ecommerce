<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_subscription_payment',
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('subscription_id')->index();
                $table->integer('payment_id')->index();
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
        Schema::dropIfExists('ecommerce_subscription_payment');
    }
}
