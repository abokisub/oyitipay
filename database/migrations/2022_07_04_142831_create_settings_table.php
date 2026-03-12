<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('is_verify_email');
            $table->integer('flutterwave');
            $table->integer('monnify_atm');
            $table->integer('monnify');
            $table->integer('wema');
            $table->integer('rolex');
            $table->integer('fed');
            $table->integer('str');
            $table->integer('earning');
            $table->integer('referral');
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
        Schema::dropIfExists('settings');
    }
}
