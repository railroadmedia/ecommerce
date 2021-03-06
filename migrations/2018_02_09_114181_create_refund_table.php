<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableRefund,
            function(Blueprint $table) {
                $table->increments('id');
                $table->integer('payment_id')->index();
                $table->decimal('payment_amount');
                $table->decimal('refunded_amount');
                $table->text('note')->nullable();
                $table->string('external_provider')->index()->nullable();
                $table->string('external_id')->index()->nullable();
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
        Schema::dropIfExists(ConfigService::$tableRefund);
    }
}
