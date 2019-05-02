<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStripeCustomerIdColumnUserStripeCustomerIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_user_stripe_customer_ids', function (Blueprint $table) {
            $table->string('stripe_customer_id', 64)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_user_stripe_customer_ids', function (Blueprint $table) {
            $table->integer('stripe_customer_id')->change();
        });
    }
}
