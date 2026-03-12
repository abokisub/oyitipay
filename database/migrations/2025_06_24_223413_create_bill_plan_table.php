<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBill_planTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('plan_id', 50)->unique();
            $table->string('disco_name', 50);
            $table->boolean('plan_status')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bill_plan');
    }
}
