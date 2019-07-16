<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoogleReceiptsTable extends Migration
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
                'ecommerce_google_receipts',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->text('purchase_token');
                    $table->string('package_name');
                    $table->string('product_id');
                    $table->string('request_type');
                    $table->string('notification_type')->nullable();
                    $table->string('email')->nullable();
                    $table->string('brand');
                    $table->boolean('valid');
                    $table->string('validation_error')->nullable();
                    $table->integer('payment_id')->nullable();
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
        Schema::dropIfExists('ecommerce_google_receipts');
    }
}
