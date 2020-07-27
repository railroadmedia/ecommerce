<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMembershipActionsTable extends Migration
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
                'ecommerce_membership_actions',
                function (Blueprint $table) {
                    $table->increments('id');

                    $table->string('action')->index();
                    $table->integer('action_amount')->index();
                    $table->integer('user_id')->index();
                    $table->integer('subscription_id')->index();
                    $table->string('brand')->index();
                    $table->text('note');

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
        Schema::dropIfExists('ecommerce_membership_actions');
    }
}
