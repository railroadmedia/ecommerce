<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SplitMethodIdForPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to drop the method_id column after the migration is done
        Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
            $table->integer('credit_card_id')->nullable()->index()->after('method_type');
            $table->integer('paypal_billing_agreement_id')->nullable()->after('credit_card_id');

            $table->string('method_id')->nullable()->change();
            $table->string('method_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
            $table->dropColumn('credit_card_id');
            $table->dropColumn('paypal_billing_agreement_id');

            $table->string('method_id')->nullable(false)->change();
            $table->string('method_type')->nullable(false)->change();
        });
    }
}
