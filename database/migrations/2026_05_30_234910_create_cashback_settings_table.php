<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashbackSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashback_settings', function (Blueprint $table) {
            $table->id();
            $table->string('service_type')->unique(); // e.g., 'airtime', 'data', 'cable', 'electricity'
            $table->decimal('cashback_amount', 10, 2)->default(0.00); // Admin sets this to 1, 0, etc.
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
        Schema::dropIfExists('cashback_settings');
    }
}
