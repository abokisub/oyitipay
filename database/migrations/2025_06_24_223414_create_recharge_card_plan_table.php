<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecharge_card_planTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recharge_card_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('network', 20);
            $table->string('plan_id', 50)->unique();
            $table->decimal('amount', 10, 2);
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
        Schema::dropIfExists('recharge_card_plan');
    }
}
