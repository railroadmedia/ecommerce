<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))->create(
            'ecommerce_refund',
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
        Schema::dropIfExists('ecommerce_refund');
    }
}
