<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdex_keyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adex_key', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('account_number', 20);
            $table->string('account_name', 100);
            $table->string('bank_name', 100);
            $table->decimal('min', 10, 2)->default(0.00);
            $table->decimal('max', 10, 2)->default(0.00);
            $table->integer('default_limit')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adex_key');
    }
}
