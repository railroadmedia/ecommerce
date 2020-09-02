<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        // https://github.com/doctrine/dbal/issues/3714 (note, is *Laravel* issue, not DBAL)
        DB::statement("ALTER TABLE ecommerce_user_stripe_customer_ids CHANGE stripe_customer_id stripe_customer_id INT");
    }
}
