<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipStatsTable extends Migration
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
                'ecommerce_membership_stats',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('new');
                    $table->integer('active_state');
                    $table->integer('expired');
                    $table->integer('suspended_state');
                    $table->integer('canceled');
                    $table->integer('canceled_state');
                    $table->string('interval_type');
                    $table->date('stats_date');
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
        Schema::dropIfExists('ecommerce_membership_stats');
    }
}
