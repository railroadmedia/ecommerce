<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Ecommerce\Services\ConfigService;


class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(ConfigService::$databaseConnectionName)->create(
            ConfigService::$tableProduct,
            function(Blueprint $table) {
                $table->increments('id');

                $table->string('brand')->index();
                $table->string('name')->index();
                $table->string('sku')->index();
                $table->decimal('price');
                $table->string('type')->index();
                $table->boolean('active')->index();
                $table->text('description')->nullable();
                $table->text('thumbnail_url')->nullable();
                $table->boolean('is_physical');
                $table->decimal('weight')->nullable();
                $table->string('subscription_interval_type')->index()->nullable();
                $table->integer('subscription_interval_count')->index()->nullable();
                $table->integer('stock')->nullable()->index();
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
        Schema::dropIfExists(ConfigService::$tableProduct);
    }
}
