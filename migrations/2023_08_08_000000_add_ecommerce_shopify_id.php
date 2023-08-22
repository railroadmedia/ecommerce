<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('digital_membership_access_expiration_date')->nullable();
        });
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('brand')->nullable();
        });
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('country')->nullable();
        });
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('billing_address_id')->nullable();
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('final_price')->nullable();
        });
        Schema::table('ecommerce_order_item_fulfillment', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('fulfilled_on')->nullable();
        });
        Schema::table('ecommerce_refunds', function (Blueprint $table) {
            $table->bigInteger('shopify_id')->after('refunded_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_order_item_fulfillment', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
        Schema::table('ecommerce_refunds', function (Blueprint $table) {
            $table->dropColumn('shopify_id');
        });
    }
};
