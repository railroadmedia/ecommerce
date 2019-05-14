<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_address',
            function(Blueprint $table) {
                $table->increments('id');

                $table->string('type')->index();
                $table->string('brand')->index();
                $table->integer('user_id')->index()->nullable();
                $table->integer('customer_id')->index()->nullable();
                $table->string('first_name')->index()->nullable();
                $table->string('last_name')->index()->nullable();
                $table->string('street_line_1')->nullable();
                $table->string('street_line_2')->nullable();
                $table->string('city')->nullable();
                $table->string('zip')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
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
        Schema::dropIfExists('ecommerce_address');
    }
}
