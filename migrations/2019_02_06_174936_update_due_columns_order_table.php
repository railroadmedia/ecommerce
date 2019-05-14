<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateDueColumnsOrderTable extends Migration
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
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('due', 'total_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('product_due')
                        ->after('total_due')
                        ->index()
                        ->nullable();
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('tax', 'taxes_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('shipping_costs', 'shipping_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('finance_due')
                        ->after('shipping_due')
                        ->index()
                        ->nullable();
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('paid', 'total_paid');
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
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_due', 'due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('product_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('taxes_due', 'tax');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('shipping_due', 'shipping_costs');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('finance_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_order',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_paid', 'paid');
                }
            );
    }
}
