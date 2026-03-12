<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateData_planTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('network', 20);
            $table->string('plan_id', 50)->unique();
            $table->string('plan_name', 100);
            $table->string('plan_size', 50);
            $table->integer('plan_day');
            $table->decimal('plan_price', 10, 2);
            $table->enum('plan_type', ['GIFTING', 'COOPERATE GIFTING', 'SME', 'DIRECT'])->default('DIRECT');
            $table->boolean('plan_status')->default(true);
            $table->decimal('smart', 10, 2)->default(0.00);
            $table->decimal('agent', 10, 2)->default(0.00);
            $table->decimal('awuf', 10, 2)->default(0.00);
            $table->decimal('api', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_plan');
    }
}
