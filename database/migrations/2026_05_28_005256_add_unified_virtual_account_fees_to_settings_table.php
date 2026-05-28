<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnifiedVirtualAccountFeesToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('virtual_account_charge_type')->default('FLAT');
            $table->decimal('virtual_account_charge_value', 10, 2)->default(0.00);
            $table->decimal('virtual_account_charge_cap', 10, 2)->default(0.00);
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
                'virtual_account_charge_type',
                'virtual_account_charge_value',
                'virtual_account_charge_cap'
            ]);
        });
    }
}
