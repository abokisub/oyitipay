<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateData_cardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_card', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 12);
            $table->string('transid', 50)->unique();
            $table->string('network', 20);
            $table->string('plan_name', 100);
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2);
            $table->enum('plan_status', ['0', '1', '2'])->default(0);
            $table->timestamp('date');
            $table->text('cards')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_card');
    }
}
