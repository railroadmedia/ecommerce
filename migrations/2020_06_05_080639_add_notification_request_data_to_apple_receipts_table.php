<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationRequestDataToAppleReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_apple_receipts', function (Blueprint $table) {
            $table->text('notification_request_data')->after('valid')->nullable();
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
            $table->dropColumn('notification_request_data');
        });
    }
}
