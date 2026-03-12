<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNetwork_gTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_g', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('mtn')->default(false);
            $table->boolean('glo')->default(false);
            $table->boolean('airtel')->default(false);
            $table->boolean('mobile')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('network_g');
    }
}
