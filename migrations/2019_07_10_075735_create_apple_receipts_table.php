<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppleReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('ecommerce.database_connection_name'))
            ->create(
                'ecommerce_apple_receipts',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->text('receipt');
                    $table->string('request_type');
                    $table->string('notification_type')->nullable();
                    $table->string('email')->nullable();
                    $table->string('brand');
                    $table->boolean('valid');
                    $table->string('validation_error')->nullable();
                    $table->integer('payment_id')->nullable();
                    $table->integer('subscription_id')->nullable();
                    $table->timestamps();
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
        Schema::dropIfExists('ecommerce_apple_receipts');
    }
}