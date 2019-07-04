<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlacedByUserIdToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->table(
            'ecommerce_orders',
            function (Blueprint $table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->integer('placed_by_user_id')->after('customer_id')->index()->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->table(
            'ecommerce_orders',
            function (Blueprint $table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->dropColumn('placed_by_user_id');
            }
        );
    }
}
