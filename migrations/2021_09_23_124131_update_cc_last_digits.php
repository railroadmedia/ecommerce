<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\Migrations\Migration;

class UpdateCcLastDigits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ecommerce_credit_cards', function (Blueprint $table) {
            $table->string('last_four_digits')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ecommerce_credit_cards', function (Blueprint $table) {
            $table->string('last_four_digits', 4)->change();
        });
    }
}
