<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaypalRecurringProfileIdToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_subscriptions', function (Blueprint $table) {
            $table->string('paypal_recurring_profile_id')->after('external_app_store_id')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_subscriptions', function (Blueprint $table) {
            $table->dropColumn('paypal_recurring_profile_id');
        });
    }
}
