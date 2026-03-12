<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('web_api', function (Blueprint $table) {
            $table->string('adex_website1', 100)->nullable();
            $table->string('adex_website2', 100)->nullable();
            $table->string('adex_website3', 100)->nullable();
            $table->string('adex_website4', 100)->nullable();
            $table->string('adex_website5', 100)->nullable();
        });

        Schema::table('data_plan', function (Blueprint $table) {
            $table->string('adex1', 255)->nullable();
            $table->string('adex2', 255)->nullable();
            $table->string('adex3', 255)->nullable();
            $table->string('adex4', 255)->nullable();
            $table->string('adex5', 255)->nullable();
        });

        Schema::table('cable_plan', function (Blueprint $table) {
            $table->string('adex1', 255)->nullable();
            $table->string('adex2', 255)->nullable();
            $table->string('adex3', 255)->nullable();
            $table->string('adex4', 255)->nullable();
            $table->string('adex5', 255)->nullable();
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            $table->string('adex1', 255)->nullable();
            $table->string('adex2', 255)->nullable();
            $table->string('adex3', 255)->nullable();
            $table->string('adex4', 255)->nullable();
            $table->string('adex5', 255)->nullable();
        });

        // Register Adex in sel table
        DB::table('sel')->insert([
            'name' => 'Adex',
            'key' => 'Adex',
            'data' => 1,
            'airtime' => 1,
            'result' => 1,
            'bill' => 1,
            'bulksms' => 1,
            'cable' => 1,
            'data_card' => 1,
            'recharge_card' => 1
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_api', function (Blueprint $table) {
            $table->dropColumn(['adex_website1', 'adex_website2', 'adex_website3', 'adex_website4', 'adex_website5']);
        });

        Schema::table('data_plan', function (Blueprint $table) {
            $table->dropColumn(['adex1', 'adex2', 'adex3', 'adex4', 'adex5']);
        });

        Schema::table('cable_plan', function (Blueprint $table) {
            $table->dropColumn(['adex1', 'adex2', 'adex3', 'adex4', 'adex5']);
        });

        Schema::table('bill_plan', function (Blueprint $table) {
            $table->dropColumn(['adex1', 'adex2', 'adex3', 'adex4', 'adex5']);
        });

        DB::table('sel')->where('name', 'Adex')->delete();
    }
};
