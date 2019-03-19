<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateAccessCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            'ecommerce_access_code',
            function(Blueprint $table) {

                /**
                 * @var $table \Illuminate\Database\Schema\Blueprint
                 */

                $table->increments('id');

                $table->string('code')->index();
                $table->string('product_ids')->index();
                $table->boolean('is_claimed')->index();
                $table->integer('claimer_id')->index()->nullable();
                $table->dateTime('claimed_on')->index()->nullable();
                $table->string('brand')->index();
                $table->dateTime('created_on')->index();
                $table->dateTime('updated_on')->index()->nullable();
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
        Schema::dropIfExists('ecommerce_access_code');
    }
}
