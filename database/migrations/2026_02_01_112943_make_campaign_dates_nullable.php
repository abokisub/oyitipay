<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCampaignDatesNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE campaigns MODIFY COLUMN start_date DATETIME NULL');
        DB::statement('ALTER TABLE campaigns MODIFY COLUMN end_date DATETIME NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE campaigns MODIFY COLUMN start_date DATETIME NOT NULL');
        DB::statement('ALTER TABLE campaigns MODIFY COLUMN end_date DATETIME NOT NULL');
    }
}
