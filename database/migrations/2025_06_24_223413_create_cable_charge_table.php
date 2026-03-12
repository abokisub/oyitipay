<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCable_chargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cable_charge', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('dstv', 5, 2)->default(0.00);
            $table->decimal('gotv', 5, 2)->default(0.00);
            $table->decimal('startimes', 5, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cable_charge');
    }
}
