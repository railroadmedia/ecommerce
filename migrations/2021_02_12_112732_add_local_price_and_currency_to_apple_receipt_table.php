<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocalPriceAndCurrencyToAppleReceiptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->decimal('local_price')->after('purchase_type')->index()->nullable();
            $table->string('local_currency')->after('local_price')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->dropColumn('local_price');
            $table->dropColumn('local_currency');
        });
    }
}
