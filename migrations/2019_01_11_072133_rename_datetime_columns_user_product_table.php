<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;

class RenameDatetimeColumnsUserProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableUserProduct,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->renameColumn('created_on', 'created_at');
            }
        );

        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableUserProduct,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
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
            ConfigService::$tableUserProduct,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->renameColumn('created_at', 'created_on');
            }
        );
        Schema::connection(ConfigService::$databaseConnectionName)->table(
            ConfigService::$tableUserProduct,
            function ($table) {
                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */
                $table->renameColumn('updated_at', 'updated_on');
            }
        );
    }
}
