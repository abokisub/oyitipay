<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $password = '@Habukhan2025';
        // Match the application's high-security bcrypt cost
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => 16]);

        $apiKey = bin2hex(openssl_random_pseudo_bytes(30));

        DB::table('user')->updateOrInsert(
            ['username' => 'Habukhan'],
            [
                'name' => 'Habukhan Admin',
                'email' => 'admin@kobopoint.com',
                'phone' => '07000000000',
                'password' => $hashedPassword,
                'apikey' => $apiKey,
                'app_key' => $apiKey,
                'bal' => 0,
                'refbal' => 0,
                'type' => 'ADMIN',
                'status' => '1',
                'kyc' => '1',
                'date' => Carbon::now("Africa/Lagos"),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Admin user "Habukhan" created successfully.');
    }
}
