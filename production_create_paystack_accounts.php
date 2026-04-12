<?php

/**
 * PRODUCTION: Create Paystack Accounts for All Users
 * This will create fresh Wema Bank AND Titan accounts for all users
 * 
 * SAFE FOR PRODUCTION:
 * - Checks if accounts already exist
 * - Handles rate limiting (3 second delay between users)
 * - Detailed logging
 * - Error recovery
 * - Progress tracking
 * 
 * Run: php production_create_paystack_accounts.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=================================================\n";
echo "PRODUCTION: CREATE PAYSTACK ACCOUNTS\n";
echo "=================================================\n\n";

// Confirmation prompt
echo "⚠️  WARNING: This will create Paystack accounts for ALL users.\n";
echo "   This operation will:\n";
echo "   - Create Titan accounts (primary)\n";
echo "   - Create Wema accounts (secondary)\n";
echo "   - Update user records\n";
echo "   - Take approximately 6 seconds per user\n\n";

echo "Are you sure you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'yes') {
    echo "Operation cancelled.\n";
    exit(0);
}
fclose($handle);

echo "\n";

// Get Paystack secret key
$paystackKey = DB::table('paystack_key')->first();
$habukhanKey = DB::table('habukhan_key')->first();

$secretKey = null;
if ($paystackKey && !empty($paystackKey->live) && $paystackKey->live !== 'sk_test_placeholder') {
    $secretKey = $paystackKey->live;
} elseif ($habukhanKey && !empty($habukhanKey->psk)) {
    $secretKey = $habukhanKey->psk;
}

if (empty($secretKey)) {
    echo "❌ ERROR: Paystack secret key not found!\n";
    echo "   Please configure Paystack keys in admin dashboard.\n";
    exit(1);
}

echo "✅ Paystack key found\n";

// Check if key is live
if (strpos($secretKey, 'sk_live_') === 0) {
    echo "✅ Using LIVE key\n\n";
} else {
    echo "⚠️  Using TEST key\n\n";
}

// Get all active users
$users = DB::table('user')->where('status', 1)->get();

echo "Found " . count($users) . " active users\n";
echo "Estimated time: " . (count($users) * 6) . " seconds\n";
echo "=================================================\n\n";

$successCount = 0;
$errorCount = 0;
$skippedCount = 0;
$errors = [];

foreach ($users as $index => $user) {
    $userNum = $index + 1;
    echo "[$userNum/" . count($users) . "] Processing: {$user->username} ({$user->email})\n";
    echo "-------------------------------------------\n";
    
    // Check if user already has both accounts
    $existingAccounts = DB::table('user_bank')
        ->where('username', $user->username)
        ->whereIn('bank', ['PAYSTACK-TITAN', 'WEMA BANK'])
        ->get();
    
    $hasTitan = $existingAccounts->where('bank', 'PAYSTACK-TITAN')->count() > 0;
    $hasWema = $existingAccounts->where('bank', 'WEMA BANK')->count() > 0;
    
    if ($hasTitan && $hasWema) {
        echo "  ⏭️  User already has both accounts, skipping...\n";
        $skippedCount++;
        echo "\n";
        continue;
    }
    
    try {
        // Step 1: Create or get customer
        $nameParts = explode(' ', $user->name, 2);
        $customerData = [
            'email' => $user->email,
            'first_name' => $nameParts[0] ?? 'User',
            'last_name' => $nameParts[1] ?? 'Account',
            'phone' => $user->phone ?? ''
        ];

        $customerResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/customer', $customerData);

        $customerCode = null;

        if ($customerResponse->successful()) {
            $customerCode = $customerResponse->json()['data']['customer_code'];
            echo "  ✅ Customer created: $customerCode\n";
        } else {
            // Try to fetch existing customer
            $fetchResponse = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json'
            ])->get('https://api.paystack.co/customer/' . urlencode($user->email));

            if ($fetchResponse->successful()) {
                $customerCode = $fetchResponse->json()['data']['customer_code'];
                echo "  ✅ Customer found: $customerCode\n";
            } else {
                throw new \Exception("Failed to create/fetch customer: " . ($customerResponse->json()['message'] ?? 'Unknown error'));
            }
        }

        // Step 2: Create Titan account (if not exists)
        if (!$hasTitan) {
            echo "  Creating Titan account...\n";
            $titanResponse = Http::timeout(30)->withHeaders([
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
                    'bank_code' => '629',
                    'date' => now()->toDateTimeString()
                ]);
            } else {
                $error = $titanResponse->json()['message'] ?? 'Unknown error';
                echo "  ⚠️  Titan creation failed: $error\n";
                $errors[] = "{$user->username}: Titan - $error";
            }
        } else {
            echo "  ⏭️  Titan account already exists\n";
        }

        // Step 3: Create Wema account (if not exists)
        if (!$hasWema) {
            echo "  Creating Wema account...\n";
            sleep(2); // Wait 2 seconds to avoid rate limiting
            
            $wemaResponse = Http::timeout(30)->withHeaders([
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
                    'bank_code' => '20',
                    'date' => now()->toDateTimeString()
                ]);
            } else {
                $error = $wemaResponse->json()['message'] ?? 'Unknown error';
                echo "  ⚠️  Wema creation failed: $error\n";
                $errors[] = "{$user->username}: Wema - $error";
            }
        } else {
            echo "  ⏭️  Wema account already exists\n";
        }

        $successCount++;
        echo "  ✅ SUCCESS\n\n";
        
        // Wait 3 seconds between users to avoid rate limiting
        if ($userNum < count($users)) {
            echo "  ⏳ Waiting 3 seconds...\n\n";
            sleep(3);
        }

    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n\n";
        $errorCount++;
        $errors[] = "{$user->username}: " . $e->getMessage();
        
        // Continue to next user
        sleep(3);
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

// Show errors if any
if (count($errors) > 0) {
    echo "ERRORS:\n";
    echo "-------\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

// Show final results
echo "Verifying accounts...\n";
$titanCount = DB::table('user_bank')
    ->where('bank', 'LIKE', '%TITAN%')
    ->orWhere('bank', 'LIKE', '%Paystack%')
    ->count();
$wemaCount = DB::table('user_bank')->where('bank', 'LIKE', '%WEMA%')->count();

echo "Titan accounts in database: $titanCount\n";
echo "Wema accounts in database: $wemaCount\n";
echo "\n✅ DONE!\n";

// Log to file
$logFile = storage_path('logs/paystack_account_creation_' . date('Y-m-d_H-i-s') . '.log');
$logContent = "Paystack Account Creation - " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Total: " . count($users) . " | Success: $successCount | Errors: $errorCount | Skipped: $skippedCount\n";
if (count($errors) > 0) {
    $logContent .= "\nErrors:\n" . implode("\n", $errors) . "\n";
}
file_put_contents($logFile, $logContent);
echo "\nLog saved to: $logFile\n";
