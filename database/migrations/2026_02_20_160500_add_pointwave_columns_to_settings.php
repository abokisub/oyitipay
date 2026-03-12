<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPointwaveColumnsToSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Add PointWave provider columns
            $table->boolean('pointwave_enabled')->default(true);
            $table->decimal('pointwave_transfer_fee', 10, 2)->default(50.00);
            $table->decimal('pointwave_min_transfer', 10, 2)->default(100.00);
            $table->decimal('pointwave_max_transfer', 10, 2)->default(5000000.00);
        });

        // Update default values if settings row exists
        DB::statement("UPDATE settings SET pointwave_enabled = 1, pointwave_transfer_fee = 50.00, pointwave_min_transfer = 100.00, pointwave_max_transfer = 5000000.00 WHERE id = 1");
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
                'pointwave_enabled',
                'pointwave_transfer_fee',
                'pointwave_min_transfer',
                'pointwave_max_transfer',
            ]);
        });
    }
}
