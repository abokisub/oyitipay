<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
        // Optional: remove it
        DB::table('sel')->where('key', 'adex')->delete();
    }
};
