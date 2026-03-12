<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('general')) {
            Schema::create('general', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('app_name', 255);
                $table->string('app_email', 255);
                $table->string('app_phone', 20);
                $table->text('app_address');
                $table->string('app_logo', 255)->nullable();
                $table->string('app_favicon', 255)->nullable();
                $table->string('currency', 10)->default('NGN');
                $table->string('currency_symbol', 5)->default('₦');
                $table->string('timezone', 50)->default('Africa/Lagos');
                $table->boolean('maintenance_mode')->default(false);
                $table->text('maintenance_message')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general');
    }
}
