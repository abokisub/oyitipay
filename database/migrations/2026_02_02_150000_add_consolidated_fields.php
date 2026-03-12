<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add cac_number to charities table
        Schema::table('charities', function (Blueprint $table) {
            if (!Schema::hasColumn('charities', 'cac_number')) {
                $table->string('cac_number')->nullable()->after('logo');
            }
        });

        // Add app download URLs to general table
        Schema::table('general', function (Blueprint $table) {
            if (!Schema::hasColumn('general', 'play_store_url')) {
                $table->string('play_store_url')->nullable();
            }
            if (!Schema::hasColumn('general', 'app_store_url')) {
                $table->string('app_store_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charities', function (Blueprint $table) {
            $table->dropColumn('cac_number');
        });

        Schema::table('general', function (Blueprint $table) {
            $table->dropColumn(['play_store_url', 'app_store_url']);
        });
    }
};
