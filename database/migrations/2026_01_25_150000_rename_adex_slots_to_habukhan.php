<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update names for the System slots (Habukhan1-5) which are currently wrongly named "ADEX WEBSITE X"
        // We do a simple string replace or specific updates.

        $slots = range(1, 5);
        foreach ($slots as $i) {
            // Check if specifically named "ADEX WEBSITE $i"
            $exists = DB::table('sel')->where('name', "ADEX WEBSITE $i")->first();
            if ($exists) {
                DB::table('sel')->where('id', $exists->id)->update(['name' => "HABUKHAN WEBSITE $i"]);
            }

            // Also check for mixed case just in case
            $existsMixed = DB::table('sel')->where('name', "Adex Website $i")->first();
            if ($existsMixed) {
                DB::table('sel')->where('id', $existsMixed->id)->update(['name' => "HABUKHAN WEBSITE $i"]);
            }
        }

        // Also ensure the single Adex vendor is just "Adex"
        DB::table('sel')->where('key', 'adex')->update(['name' => 'Adex']);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert is complex without tracking previous state, skipping for data fix.
    }
};
