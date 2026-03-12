<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // User requested to ADD "ADEX WEBSITE 1" to "ADEX WEBSITE 5" to the selection list.
        // These should be new entries, distinct from the HABUKHAN ones.

        $names = [
            'ADEX WEBSITE 1',
            'ADEX WEBSITE 2',
            'ADEX WEBSITE 3',
            'ADEX WEBSITE 4',
            'ADEX WEBSITE 5',
        ];

        foreach ($names as $index => $name) {
            // Check existence to avoid dupes
            $exists = DB::table('sel')->where('name', $name)->first();
            if (!$exists) {
                DB::table('sel')->insert([
                    'name' => $name,
                    'key' => 'adex_website_' . ($index + 1), // unique key
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
