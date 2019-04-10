<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateUserStripeCustomerIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            'ecommerce_user_stripe_customer_ids',
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->index();
                $table->integer('stripe_customer_id')->index();
                $table->dateTime('created_at')->index();
                $table->dateTime('updated_at')->index()->nullable();
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
        Schema::dropIfExists('ecommerce_user_stripe_customer_ids');
    }
}
