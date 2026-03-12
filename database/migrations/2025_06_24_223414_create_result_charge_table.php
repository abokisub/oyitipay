<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResult_chargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('result_charge', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('waec', 10, 2)->default(0.00);
            $table->decimal('neco', 10, 2)->default(0.00);
            $table->decimal('nabteb', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('result_charge');
    }
}
