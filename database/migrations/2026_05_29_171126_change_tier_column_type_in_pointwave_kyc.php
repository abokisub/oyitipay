<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeTierColumnTypeInPointwaveKyc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change the column to a string using raw SQL to avoid doctrine/dbal requirement
        DB::statement('ALTER TABLE pointwave_kyc MODIFY tier VARCHAR(50) DEFAULT "tier_1"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert back to enum
        DB::statement('ALTER TABLE pointwave_kyc MODIFY tier ENUM("tier_1", "tier_2", "tier_3") DEFAULT "tier_1"');
    }
}
