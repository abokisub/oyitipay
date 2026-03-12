<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBank_transferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_transfer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 12);
            $table->decimal('amount', 10, 2);
            $table->string('account_number', 20);
            $table->string('account_name', 100);
            $table->string('bank_name', 100);
            $table->string('reference', 100)->unique();
            $table->enum('plan_status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
            $table->timestamp('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_transfer');
    }
}
