<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedOnColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function (Blueprint $table) {
                    $table->dateTime('deleted_on')->index()->nullable();
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function (Blueprint $table) {
                    $table->dateTime('deleted_on')->index()->nullable();
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_subscription',
                function (Blueprint $table) {
                    $table->dateTime('deleted_on')->index()->nullable();
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
        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function (Blueprint $table) {
                    $table->dropColumn('deleted_on');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function (Blueprint $table) {
                    $table->dropColumn('deleted_on');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_subscription',
                function (Blueprint $table) {
                    $table->dropColumn('deleted_on');
                }
            );
    }
}
