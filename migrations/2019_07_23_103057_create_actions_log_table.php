<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActionsLogTable extends Migration
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
                'ecommerce_actions_log',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('brand');
                    $table->string('resource_name');
                    $table->integer('resource_id');
                    $table->string('action_name');
                    $table->string('actor');
                    $table->integer('actor_id')->nullable();
                    $table->string('actor_role')->nullable();
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
        Schema::dropIfExists('ecommerce_actions_log');
    }
}
