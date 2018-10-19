<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class AddVisibleToDicountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableDiscount,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->boolean('visible')->after('active')->index()->nullable();
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
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableDiscount,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->dropColumn('visible');
            }
        );
    }
}
