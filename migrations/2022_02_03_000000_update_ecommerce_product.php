<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEcommerceProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->integer('digital_access_time_interval_length')->after('auto_decrement_stock')->nullable();
            $table->string('digital_access_time_type')->after('auto_decrement_stock')->nullable();
            $table->string('digital_access_time_interval_type')->after('auto_decrement_stock')->nullable();
            $table->string('digital_access_type')->after('auto_decrement_stock')->nullable();
            $table->integer('public_stock_count')->after('stock')->nullable();
            $table->text('digital_access_permission_names')->after('auto_decrement_stock')->nullable();
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
            $table->dropColumn('digital_access_time_interval_length');
            $table->dropColumn('public_stock_count');
            $table->dropColumn('digital_access_time_type');
            $table->dropColumn('digital_access_time_interval_type');
            $table->dropColumn('digital_access_type');
            $table->dropColumn('digital_access_permission_names');
        });
    }
}
