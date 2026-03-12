<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillselTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billsel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ikeja', 50);
            $table->string('eko', 50);
            $table->string('kano', 50);
            $table->string('phcn', 50);
            $table->string('jos', 50);
            $table->string('kaduna', 50);
            $table->string('enugu', 50);
            $table->string('benin', 50);
            $table->string('port_harcourt', 50);
            $table->string('ibadan', 50);
            $table->string('abuja', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billsel');
    }
}
