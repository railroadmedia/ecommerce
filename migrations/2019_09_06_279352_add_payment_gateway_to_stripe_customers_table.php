<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentGatewayToStripeCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_user_stripe_customer_ids', function (Blueprint $table) {
            $table->string('payment_gateway_name')->after('stripe_customer_id');
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
            $table->dropColumn('payment_gateway_name');
        });
    }
}
