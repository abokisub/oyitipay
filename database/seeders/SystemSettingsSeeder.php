<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. General Settings
        DB::table('general')->updateOrInsert(
            ['id' => 1],
            [
                'app_name' => 'Kobopoint',
                'app_email' => 'support@kobopoint.com',
                'app_phone' => '07000000000',
                'app_address' => 'Nigeria',
                'currency' => 'NGN',
                'currency_symbol' => '₦',
                'timezone' => 'Africa/Lagos',
                'maintenance_mode' => false,
            ]
        );

        // 2. Feature Status
        $features = [
            ['name' => 'Airtime', 'status' => 1],
            ['name' => 'Data', 'status' => 1],
            ['name' => 'Cable TV', 'status' => 1],
            ['name' => 'Electricity', 'status' => 1],
            ['name' => 'Exam PIN', 'status' => 1],
            ['name' => 'Airtime 2 Cash', 'status' => 1],
            ['name' => 'Charity', 'status' => 1],
            ['name' => 'Virtual Card', 'status' => 1],
            ['name' => 'Bulk SMS', 'status' => 1],
        ];
        foreach ($features as $feature) {
            DB::table('feature')->updateOrInsert(['name' => $feature['name']], $feature);
        }

        // 3. System Vending Selection (sel table)
        $vendors = [
            ['name' => 'VTPass', 'key' => 'vtpass'],
            ['name' => 'Adex', 'key' => 'adex'],
            ['name' => 'Habukhan', 'key' => 'habukhan'],
        ];
        foreach ($vendors as $v) {
            DB::table('sel')->updateOrInsert(['key' => $v['key']], $v);
        }

        // 4. App Settings
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_verify_email' => 1,
                'flutterwave' => 0,
                'monnify_atm' => 1,
                'monnify' => 1,
                'wema' => 1,
                'rolex' => 1,
                'fed' => 1,
                'str' => 1,
                'earning' => 1,
                'referral' => 1,
                'palmpay_enabled' => 1,
                'monnify_enabled' => 1,
                'wema_enabled' => 1,
                'xixapay_enabled' => 1,
                'default_virtual_account' => 'palmpay',
            ]
        );

        // 5. Habukhan (Legacy Adex) Key Settings
        DB::table('habukhan_key')->updateOrInsert(
            ['id' => 1],
            [
                'account_number' => '0000000000',
                'account_name' => 'Habukhan System',
                'bank_name' => 'Central Bank',
                'min' => 100.00,
                'max' => 500000.00,
                'default_limit' => 100000,
            ]
        );

        $this->command->info('System settings seeded successfully.');
    }
}
