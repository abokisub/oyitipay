<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirtimeselTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airtimesel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mtn', 50);
            $table->string('glo', 50);
            $table->string('airtel', 50);
            $table->string('mobile', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airtimesel');
    }
}
