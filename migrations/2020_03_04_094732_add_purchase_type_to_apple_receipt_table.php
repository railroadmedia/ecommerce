<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseTypeToAppleReceiptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->text('purchase_type')->after('raw_receipt_response')->nullable();
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
            $table->dropColumn('purchase_type');
        });
    }
}
