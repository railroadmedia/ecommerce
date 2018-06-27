<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableSubscription,
            function(Blueprint $table) {
                $table->increments('id');

                $table->string('brand')->index();
                $table->string('type')->index();
                $table->integer('user_id')->index()->nullable();
                $table->integer('customer_id')->index()->nullable();
                $table->integer('order_id')->index()->nullable();
                $table->integer('product_id')->index()->nullable();
                $table->boolean('is_active')->index();
                $table->dateTime('start_date')->index();
                $table->dateTime('paid_until')->index();
                $table->dateTime('canceled_on')->nullable();
                $table->text('note')->nullable();
                $table->decimal('total_price_per_payment');
                $table->decimal('tax_per_payment')->nullable();
                $table->decimal('shipping_per_payment')->nullable();
                $table->string('currency', 3)->index();
                $table->string('interval_type')->index();
                $table->integer('interval_count');
                $table->integer('total_cycles_due')->nullable();
                $table->integer('total_cycles_paid');
                $table->integer('payment_method_id')->index()->nullable();
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
        Schema::dropIfExists(ConfigService::$tableSubscription);
    }
}
