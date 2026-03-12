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
        Schema::table('data_plan', function (Blueprint $table) {
            if (!Schema::hasColumn('data_plan', 'adex1')) {
                $table->string('adex1')->nullable();
            }
            if (!Schema::hasColumn('data_plan', 'adex2')) {
                $table->string('adex2')->nullable();
            }
            if (!Schema::hasColumn('data_plan', 'adex3')) {
                $table->string('adex3')->nullable();
            }
            if (!Schema::hasColumn('data_plan', 'adex4')) {
                $table->string('adex4')->nullable();
            }
            if (!Schema::hasColumn('data_plan', 'adex5')) {
                $table->string('adex5')->nullable();
            }
        });

        // Insert Adex into sel table if not exists
        if (DB::table('sel')->where('key', 'adex')->count() == 0) {
            DB::table('sel')->insert([
                'name' => 'Adex',
                'key' => 'adex',
                'data' => 1,
                'airtime' => 1,
                'cable' => 1,
                'bill' => 1,
                'bulksms' => 1,
                'result' => 1,
                'data_card' => 1,
                'recharge_card' => 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_plan', function (Blueprint $table) {
            $table->dropColumn(['adex1', 'adex2', 'adex3', 'adex4', 'adex5']);
        });

        DB::table('sel')->where('key', 'adex')->delete();
    }
};
