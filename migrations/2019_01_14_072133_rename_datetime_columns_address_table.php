<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class RenameDatetimeColumnsAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableAddress,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->renameColumn('created_on', 'created_at');
                $table->renameColumn('updated_on', 'updated_at');
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
            ConfigService::$tableAddress,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->renameColumn('created_at', 'created_on');
                $table->renameColumn('updated_at', 'updated_on');
            }
        );
    }
}
