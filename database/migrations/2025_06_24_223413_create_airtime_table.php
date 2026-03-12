<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirtimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airtime', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 12);
            $table->string('transid', 50)->unique();
            $table->string('network', 20);
            $table->string('phone', 11);
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2);
            $table->enum('plan_status', ['0', '1', '2'])->default(0);
            $table->timestamp('date');
            $table->string('type', 20)->default('VTU');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airtime');
    }
}
