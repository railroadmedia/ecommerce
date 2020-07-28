<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActionReasonToMembershipActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_membership_actions', function (Blueprint $table) {
            $table->dateTime('action_reason')->after('action_amount')->nullable();
            $table->integer('action_amount')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_membership_actions', function (Blueprint $table) {
            $table->dropColumn('action_reason');
            $table->integer('action_amount')->nullable(false)->change();
        });
    }
}
