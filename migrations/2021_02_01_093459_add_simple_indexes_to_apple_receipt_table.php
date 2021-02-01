<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSimpleIndexesToAppleReceiptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->index('transaction_id', 'ecommerce_apple_receipts_transaction_id_index');
            $table->index('request_type', 'ecommerce_apple_receipts_request_type_index');
            $table->index('notification_type', 'ecommerce_apple_receipts_notification_type_index');
            $table->index('email', 'ecommerce_apple_receipts_email_index');
            $table->index('brand', 'ecommerce_apple_receipts_brand_index');
            $table->index('valid', 'ecommerce_apple_receipts_valid_index');
            $table->index('payment_id', 'ecommerce_apple_receipts_payment_id_index');
            $table->index('subscription_id', 'ecommerce_apple_receipts_subscription_id_index');
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
            $table->dropIndex('ecommerce_apple_receipts_transaction_id_index');
            $table->dropIndex('ecommerce_apple_receipts_request_type_index');
            $table->dropIndex('ecommerce_apple_receipts_notification_type_index');
            $table->dropIndex('ecommerce_apple_receipts_email_index');
            $table->dropIndex('ecommerce_apple_receipts_brand_index');
            $table->dropIndex('ecommerce_apple_receipts_valid_index');
            $table->dropIndex('ecommerce_apple_receipts_payment_id_index');
            $table->dropIndex('ecommerce_apple_receipts_subscription_id_index');
        });
    }
}
