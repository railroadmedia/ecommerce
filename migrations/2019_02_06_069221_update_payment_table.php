<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class UpdatePaymentTable extends Migration
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
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('due', 'total_due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('paid', 'total_paid');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('refunded', 'total_refunded');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table
                        ->decimal('conversion_rate', 8, 2)
                        ->after('total_refunded')
                        ->nullable();
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
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_due', 'due');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_paid', 'paid');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->renameColumn('total_refunded', 'refunded');
                }
            );

        Schema::connection(config('ecommerce.database_connection_name'))
            ->table(
                'ecommerce_payment',
                function ($table) {
                    /**
                     * @var $table \Illuminate\Database\Schema\Blueprint
                     */
                    $table->dropColumn('conversion_rate');
                }
            );
    }
}
