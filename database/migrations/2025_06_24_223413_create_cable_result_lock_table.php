<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCable_result_lockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cable_result_lock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('dstv')->default(false);
            $table->boolean('gotv')->default(false);
            $table->boolean('startimes')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cable_result_lock');
    }
}
