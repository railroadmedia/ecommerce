<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGatewayToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->string('gateway_name')->after('external_provider')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->dropColumn('gateway_name');
        });
    }
}
