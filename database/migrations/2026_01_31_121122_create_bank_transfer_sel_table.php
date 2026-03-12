<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankTransferSelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_transfer_sel', function (Blueprint $table) {
            $table->id();
            $table->string('bank_transfer')->default('monnify');
            $table->timestamps();
        });

        DB::table('bank_transfer_sel')->insert([
            'bank_transfer' => 'monnify',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_transfer_sel');
    }
}
