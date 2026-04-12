<?php
/**
 * Check Paystack accounts in database
 * Run on production: php check_paystack_accounts.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "CHECKING PAYSTACK ACCOUNTS IN DATABASE\n";
echo str_repeat("=", 70) . "\n\n";

// Check user_bank table for Paystack accounts
$paystackAccounts = DB::table('user_bank')
    ->whereIn('bank', ['PAYSTACK-TITAN', 'WEMA BANK', 'TITAN PAYSTACK', 'Titan Paystack', 'Wema Bank'])
    ->orWhere('bank', 'LIKE', '%TITAN%')
    ->orWhere('bank', 'LIKE', '%PAYSTACK%')
    ->orWhere('bank', 'LIKE', '%WEMA%')
    ->get();

echo "Found " . count($paystackAccounts) . " Paystack-related accounts\n\n";

// Group by bank name
$grouped = [];
foreach ($paystackAccounts as $account) {
    $bankName = $account->bank;
    if (!isset($grouped[$bankName])) {
        $grouped[$bankName] = [];
    }
    $grouped[$bankName][] = $account;
}

echo "ACCOUNTS BY BANK NAME:\n";
echo str_repeat("-", 70) . "\n";
foreach ($grouped as $bankName => $accounts) {
    echo "\n\"$bankName\": " . count($accounts) . " accounts\n";
    
    // Show first 3 examples
    $examples = array_slice($accounts, 0, 3);
    foreach ($examples as $acc) {
        echo "  - {$acc->username}: {$acc->account_number}\n";
    }
    if (count($accounts) > 3) {
        echo "  ... and " . (count($accounts) - 3) . " more\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "CHECKING SPECIFIC USER: Habukhan\n";
echo str_repeat("=", 70) . "\n\n";

$habukhanAccounts = DB::table('user_bank')
    ->where('username', 'Habukhan')
    ->get();

echo "Habukhan has " . count($habukhanAccounts) . " accounts:\n\n";
foreach ($habukhanAccounts as $acc) {
    echo "Bank: {$acc->bank}\n";
    echo "Account Number: {$acc->account_number}\n";
    echo "Account Name: " . ($acc->bank_name ?? 'NULL') . "\n";
    echo "Bank Code: " . ($acc->bank_code ?? 'NULL') . "\n";
    echo "---\n";
}

// Now simulate getUserVirtualAccounts for Habukhan
echo "\n" . str_repeat("=", 70) . "\n";
echo "SIMULATING getUserVirtualAccounts() FOR Habukhan\n";
echo str_repeat("=", 70) . "\n\n";

$accounts = [];

foreach ($habukhanAccounts as $bank) {
    $provider = 'unknown';
    $bankName = $bank->bank;
    
    echo "Processing: $bankName\n";
    
    // Determine provider based on bank name
    if (stripos($bankName, 'TITAN') !== false) {
        $provider = 'titan';
        $bankName = 'PAYSTACK-TITAN';
        echo "  → Detected as TITAN\n";
    } elseif (stripos($bankName, 'PAYSTACK') !== false) {
        $provider = 'paystack';
        $bankName = 'PAYSTACK-TITAN';
        echo "  → Detected as PAYSTACK\n";
    } elseif (stripos($bankName, 'WEMA') !== false) {
        $provider = 'wema';
        $bankName = 'WEMA BANK';
        echo "  → Detected as WEMA\n";
    } elseif (stripos($bankName, 'MONIEPOINT') !== false) {
        $provider = 'monnify';
        $bankName = 'MONIEPOINT';
        echo "  → Detected as MONIEPOINT\n";
    }
    
    $accounts[] = [
        'provider' => $provider,
        'bank_name' => $bankName,
        'account_number' => $bank->account_number,
        'account_name' => $bank->bank_name ?? null
    ];
    echo "\n";
}

echo "RESULT:\n";
echo json_encode($accounts, JSON_PRETTY_PRINT) . "\n\n";

echo "EXPECTED:\n";
echo "- Should have 2 Paystack accounts\n";
echo "- One with provider='titan' and bank_name='PAYSTACK-TITAN'\n";
echo "- One with provider='wema' and bank_name='WEMA BANK'\n";
echo "- Each with DIFFERENT account numbers\n";
