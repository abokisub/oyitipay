<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameRolexToKolomoniMfb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('user', 'rolex')) {
            DB::statement('ALTER TABLE user CHANGE rolex kolomoni_mfb VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('settings', 'rolex')) {
            DB::statement('ALTER TABLE settings CHANGE rolex kolomoni_mfb VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('user', 'kolomoni_mfb')) {
            DB::statement('ALTER TABLE user CHANGE kolomoni_mfb rolex VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('settings', 'kolomoni_mfb')) {
            DB::statement('ALTER TABLE settings CHANGE kolomoni_mfb rolex VARCHAR(255) NULL');
        }
    }
}
