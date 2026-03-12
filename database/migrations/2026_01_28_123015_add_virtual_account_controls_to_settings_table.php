<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVirtualAccountControlsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('palmpay_enabled')->default(true);
            $table->boolean('monnify_enabled')->default(true);
            $table->boolean('wema_enabled')->default(true);
            $table->boolean('xixapay_enabled')->default(true);
            $table->string('default_virtual_account', 20)->default('palmpay');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'palmpay_enabled',
                'monnify_enabled',
                'wema_enabled',
                'xixapay_enabled',
                'default_virtual_account'
            ]);
        });
    }
}
