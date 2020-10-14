<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeActionReasonToStringMembershipActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_membership_actions', function (Blueprint $table) {
            $table->string('action_reason')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // https://github.com/doctrine/dbal/issues/3714 (note, is *Laravel* issue, not DBAL)
        DB::statement("ALTER TABLE ecommerce_membership_actions CHANGE action_reason action_reason DATETIME NULL");
    }
}
