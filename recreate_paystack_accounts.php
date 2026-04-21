<?php

/**
 * Recreate Paystack Accounts for All Users
 * This will create fresh Wema Bank AND Titan accounts for all users
 * 
 * Run: php recreate_paystack_accounts.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=================================================\n";
echo "RECREATE PAYSTACK ACCOUNTS FOR ALL USERS\n";
echo "=================================================\n\n";

// Get Paystack secret key
$habukhanKey = DB::table('habukhan_key')->first();
$secretKey = $habukhanKey->psk ?? null;

if (empty($secretKey)) {
    echo "❌ ERROR: Paystack secret key not found!\n";
    exit(1);
}

echo "✅ Paystack key found\n\n";

// Get all users
$users = DB::table('user')->where('status', 1)->get();

echo "Found " . count($users) . " active users\n";
echo "=================================================\n\n";

$successCount = 0;
$errorCount = 0;
$skippedCount = 0;

foreach ($users as $user) {
    echo "Processing: {$user->username} ({$user->email})\n";
    echo "-------------------------------------------\n";
    
    // Clear existing Paystack accounts
    echo "  Clearing old accounts...\n";
    DB::table('user')->where('id', $user->id)->update([
        'paystack_account' => null,
        'paystack_bank' => null
    ]);
    
    DB::table('user_bank')
        ->where('username', $user->username)
        ->whereIn('bank', ['WEMA BANK', 'PAYSTACK-TITAN', 'Wema Bank', 'Paystack-Titan'])
        ->delete();
    
    try {
        // Step 1: Create or get customer
        $nameParts = explode(' ', $user->name, 2);
        $customerData = [
            'email' => $user->email,
            'first_name' => $nameParts[0] ?? 'User',
            'last_name' => $nameParts[1] ?? 'Account',
            'phone' => $user->phone ?? ''
        ];

        $customerResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/customer', $customerData);

        $customerCode = null;

        if ($customerResponse->successful()) {
            $customerCode = $customerResponse->json()['data']['customer_code'];
            echo "  ✅ Customer created: $customerCode\n";
        } else {
            // Try to fetch existing customer
            $fetchResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json'
            ])->get('https://api.paystack.co/customer/' . urlencode($user->email));

            if ($fetchResponse->successful()) {
                $customerCode = $fetchResponse->json()['data']['customer_code'];
                echo "  ✅ Customer found: $customerCode\n";
            } else {
                echo "  ❌ Failed to create/fetch customer\n";
                $errorCount++;
                continue;
            }
        }

        // Step 2: Create Titan account (primary)
        echo "  Creating Titan account...\n";
        $titanResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/dedicated_account', [
            'customer' => $customerCode,
            'preferred_bank' => 'titan-paystack'
        ]);

        if ($titanResponse->successful()) {
            $titanInfo = $titanResponse->json()['data'];
            $titanAccount = $titanInfo['account_number'];
            $titanName = $titanInfo['account_name'];
            $titanBank = $titanInfo['bank']['name'];
            
            echo "  ✅ Titan: $titanAccount ($titanBank)\n";
            
            // Save Titan as primary account
            DB::table('user')->where('id', $user->id)->update([
                'paystack_account' => $titanAccount,
                'paystack_bank' => $titanBank
            ]);
            
            // Add to user_bank
            DB::table('user_bank')->insert([
                'username' => $user->username,
                'account_number' => $titanAccount,
                'bank_name' => $titanName,
                'bank' => strtoupper($titanBank),
                'bank_code' => '629', // Titan bank code
                'date' => now()->toDateTimeString()
            ]);
        } else {
            echo "  ⚠️  Titan creation failed: " . ($titanResponse->json()['message'] ?? 'Unknown error') . "\n";
        }

        // Step 3: Create Wema account (secondary)
        echo "  Creating Wema account...\n";
        sleep(2); // Wait 2 seconds to avoid rate limiting
        
        $wemaResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/dedicated_account', [
            'customer' => $customerCode,
            'preferred_bank' => 'wema-bank'
        ]);

        if ($wemaResponse->successful()) {
            $wemaInfo = $wemaResponse->json()['data'];
            $wemaAccount = $wemaInfo['account_number'];
            $wemaName = $wemaInfo['account_name'];
            $wemaBank = $wemaInfo['bank']['name'];
            
            echo "  ✅ Wema: $wemaAccount ($wemaBank)\n";
            
            // Add to user_bank
            DB::table('user_bank')->insert([
                'username' => $user->username,
                'account_number' => $wemaAccount,
                'bank_name' => $wemaName,
                'bank' => 'WEMA BANK',
                'bank_code' => '20', // Wema bank code
                'date' => now()->toDateTimeString()
            ]);
        } else {
            echo "  ⚠️  Wema creation failed: " . ($wemaResponse->json()['message'] ?? 'Unknown error') . "\n";
        }

        $successCount++;
        echo "  ✅ SUCCESS\n\n";
        
        // Wait 3 seconds between users to avoid rate limiting
        sleep(3);

    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n\n";
        $errorCount++;
    }
}

echo "=================================================\n";
echo "SUMMARY\n";
echo "=================================================\n";
echo "Total Users: " . count($users) . "\n";
echo "✅ Success: $successCount\n";
echo "❌ Errors: $errorCount\n";
echo "⏭️  Skipped: $skippedCount\n";
echo "=================================================\n\n";

// Show final results
echo "Verifying accounts...\n";
$titanCount = DB::table('user_bank')->where('bank', 'LIKE', '%TITAN%')->orWhere('bank', 'LIKE', '%Paystack%')->count();
$wemaCount = DB::table('user_bank')->where('bank', 'LIKE', '%WEMA%')->count();

echo "Titan accounts created: $titanCount\n";
echo "Wema accounts created: $wemaCount\n";
echo "\n✅ DONE!\n";
