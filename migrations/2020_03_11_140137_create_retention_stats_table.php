<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRetentionStatsTable extends Migration
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
                'ecommerce_retention_stats',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('subscription_type');
                    $table->date('interval_start_date');
                    $table->date('interval_end_date');
                    $table->string('brand');
                    $table->integer('customers_start');
                    $table->integer('customers_end');
                    $table->integer('customers_new');
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
        Schema::dropIfExists('ecommerce_retention_stats');
    }
}
