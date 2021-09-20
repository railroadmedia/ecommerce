<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\Migrations\Migration;

class AddAppleReceiptIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!(Schema::connection(config('ecommerce.database_connection_name'))->getConnection() instanceof SQLiteConnection)) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table(
                    'ecommerce_apple_receipts',
                    function (Blueprint $table) {
                        /**
                         * @var $table \Illuminate\Database\Schema\Blueprint
                         */
                        $table->index([DB::raw('receipt(255)')], 'ecommerce_apple_receipts_receipt_index');
                    }
                );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!(Schema::connection(config('ecommerce.database_connection_name'))->getConnection() instanceof SQLiteConnection)) {
            Schema::connection(config('ecommerce.database_connection_name'))
                ->table(
                    'ecommerce_apple_receipts',
                    function (Blueprint $table) {
                        /**
                         * @var $table \Illuminate\Database\Schema\Blueprint
                         */
                        $table->dropIndex('ecommerce_apple_receipts_receipt_index');
                    }
                );
        }
    }
}
