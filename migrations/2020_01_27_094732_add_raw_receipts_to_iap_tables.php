<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRawReceiptsToIapTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_google_receipts', function (Blueprint $table) {
            $table->text('raw_receipt_response')->after('order_id')->nullable();
        });

        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->text('raw_receipt_response')->after('subscription_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_google_receipts', function (Blueprint $table) {
            $table->dropColumn('raw_receipt_response');
        });

        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->dropColumn('raw_receipt_response');
        });
    }
}
