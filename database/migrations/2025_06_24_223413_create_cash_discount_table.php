<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCash_discountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_discount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mtn_number', 11);
            $table->string('glo_number', 11);
            $table->string('airtel_number', 11);
            $table->string('mobile_number', 11);
            $table->decimal('mtn_rate', 5, 2)->default(0.00);
            $table->decimal('glo_rate', 5, 2)->default(0.00);
            $table->decimal('airtel_rate', 5, 2)->default(0.00);
            $table->decimal('mobile_rate', 5, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_discount');
    }
}
