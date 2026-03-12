<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNetworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('network', 20);
            $table->string('plan_id', 50)->unique();
            $table->boolean('network_vtu')->default(true);
            $table->boolean('network_share')->default(true);
            $table->boolean('network_sme')->default(true);
            $table->boolean('network_cg')->default(true);
            $table->boolean('network_g')->default(true);
            $table->boolean('cash')->default(true);
            $table->boolean('data_card')->default(true);
            $table->boolean('recharge_card')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('network');
    }
}
