<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteColumnToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_access_codes', function (Blueprint $table) {
            $table->text('note')->after('brand')->nullable();
        });
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->text('note')->after('country')->nullable();
        });
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->text('note')->after('brand')->nullable();
        });
        Schema::table('ecommerce_discounts', function (Blueprint $table) {
            $table->text('note')->after('visible')->nullable();
        });
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->text('note')->after('billing_address_id')->nullable();
        });
        Schema::table('ecommerce_order_item_fulfillment', function (Blueprint $table) {
            $table->text('note')->after('fulfilled_on')->nullable();
        });
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->text('note')->after('currency')->nullable();
        });
        Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
            $table->text('note')->after('billing_address_id')->nullable();
        });
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->text('note')->after('stock')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_access_codes', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_discounts', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_order_item_fulfillment', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
            $table->dropColumn('note');
        });
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
}
