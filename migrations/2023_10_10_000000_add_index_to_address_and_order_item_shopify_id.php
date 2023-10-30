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
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->index('shopify_id', 'ecommerce_addresses_shopify_id_index');
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->index('shopify_id', 'ecommerce_order_items_shopify_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_addresses', function (Blueprint $table) {
            $table->dropIndex('ecommerce_addresses_shopify_id_index');
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            $table->dropIndex('ecommerce_order_items_shopify_id_index');
        });
    }
};
