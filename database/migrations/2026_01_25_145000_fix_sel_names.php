<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename existing System/Habukhan entry to "Habukhan"
        // precise key might be 'Habukhan1' based on frontend default, or 'system'
        // We update based on likeliness. 
        // If there is an entry with key 'Habukhan1', name it 'Habukhan'

        $exists = DB::table('sel')->where('key', 'Habukhan1')->first();
        if ($exists) {
            DB::table('sel')->where('key', 'Habukhan1')->update(['name' => 'Habukhan']);
        } else {
            // If it doesn't exist, create it (The "System" one)
            DB::table('sel')->insert([
                'name' => 'Habukhan',
                'key' => 'Habukhan1',
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

        // 2. Ensure "Adex" exists with key 'adex'
        $adexExists = DB::table('sel')->where('key', 'adex')->first();
        if ($adexExists) {
            DB::table('sel')->where('key', 'adex')->update(['name' => 'Adex']);
        } else {
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
        // No specific reverse needed for a pure data update in this context
    }
};
