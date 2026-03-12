<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCable_planTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cable_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cable_name', 50);
            $table->string('plan_id', 50)->unique();
            $table->string('plan_name', 100);
            $table->decimal('plan_price', 10, 2);
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
        Schema::dropIfExists('cable_plan');
    }
}
