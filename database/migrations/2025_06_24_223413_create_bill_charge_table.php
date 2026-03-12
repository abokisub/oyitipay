<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBill_chargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_charge', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('ikeja', 5, 2)->default(0.00);
            $table->decimal('eko', 5, 2)->default(0.00);
            $table->decimal('kano', 5, 2)->default(0.00);
            $table->decimal('phcn', 5, 2)->default(0.00);
            $table->decimal('jos', 5, 2)->default(0.00);
            $table->decimal('kaduna', 5, 2)->default(0.00);
            $table->decimal('enugu', 5, 2)->default(0.00);
            $table->decimal('benin', 5, 2)->default(0.00);
            $table->decimal('port_harcourt', 5, 2)->default(0.00);
            $table->decimal('ibadan', 5, 2)->default(0.00);
            $table->decimal('abuja', 5, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bill_charge');
    }
}
