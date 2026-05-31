<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // 'airtime' or 'data'
            $table->string('network'); // 'MTN', 'AIRTEL', etc.
            $table->decimal('amount', 10, 2);
            $table->integer('data_plan_id')->nullable(); // Foreign key or ID to data plan if data
            $table->string('vtu_type')->nullable(); // 'vtu' or 'sharesell' if airtime
            $table->string('status')->default('unused'); // 'unused', 'used'
            $table->string('used_by')->nullable(); // username of the user who claimed it
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
}
