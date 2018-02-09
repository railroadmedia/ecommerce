<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateCreditCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableCreditCard,
            function(Blueprint $table) {
                $table->increments('id');
                $table->string('type')->index();
                $table->string('fingerprint');
                $table->integer('last_four_digits');
                $table->string('cardholder_name')->nullable();
                $table->string('company_name')->index();
                $table->dateTime('expiration_date');
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
        Schema::dropIfExists(ConfigService::$tableCreditCard);
    }
}
