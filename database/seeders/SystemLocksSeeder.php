<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemLocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $features = ['kyc', 'card_ngn', 'card_usd'];

        foreach ($features as $key) {
            DB::table('system_locks')->updateOrInsert(
                ['feature_key' => $key],
                ['is_locked' => false, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
