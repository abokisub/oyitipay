<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$targetUsername = 'oshafu';
$user = User::where('username', $targetUsername)->first();

if (!$user) {
    echo "User $targetUsername not found.\n";
    exit(1);
}

echo "==================================================\n";
echo "🔍 CORRECTED DETAILED FRAUD AUDIT: " . strtoupper($user->username) . " (ID: {$user->id})\n";
echo "==================================================\n\n";

// 1. Core Profile Details
echo "👤 [1] USER ACCOUNT RECORD\n";
echo "Name:          {$user->name}\n";
echo "Email:         {$user->email}\n";
echo "Phone:         {$user->phone}\n";
echo "BVN:           " . ($user->bvn ?? 'NULL') . "\n";
echo "NIN:           " . ($user->nin ?? 'NULL') . "\n";
echo "Registered:    {$user->date}\n";
echo "Status:        {$user->status}\n";
echo "Balance:       ₦" . number_format($user->bal, 2) . "\n\n";

// 2. Anomaly Source Investigation (With correct status names)
echo "💰 [2] FUNDING SOURCE CHECK\n";
$successDeposits = DB::table('deposit')->where('username', $user->username)->where('status', 1)->sum('amount');
$allDepositsSum = DB::table('deposit')->where('username', $user->username)->sum('amount');

echo "Manual Admin Funding (deposit table):      ₦" . number_format($successDeposits, 2) . "\n";

// Check if they were credited through PointWave virtual accounts
$pwCredits = 0;
if (Schema::hasTable('pointwave_transactions')) {
    // Note: status is 'completed' in PointWaveTransaction model/table
    $pwCredits = DB::table('pointwave_transactions')
        ->where('user_id', $user->id)
        ->where('status', 'completed')
        ->sum('amount');
    
    $pwCount = DB::table('pointwave_transactions')
        ->where('user_id', $user->id)
        ->where('status', 'completed')
        ->count();
        
    echo "Successful PointWave Deposits (DVA):       ₦" . number_format($pwCredits, 2) . " ($pwCount successful transactions)\n";
}

$totalInflow = $successDeposits + $pwCredits;
echo "--------------------------------------------------\n";
echo "TOTAL ACTUAL INFLOW (Deposits + DVA):       ₦" . number_format($totalInflow, 2) . "\n\n";

// 3. Bank Transfer Audit
echo "💸 [3] BANK TRANSFER STATUS SUMS\n";
$successTransfers = DB::table('transfers')->where('user_id', $user->id)->where('status', 'SUCCESS')->sum('amount');
$pendingTransfers = DB::table('transfers')->where('user_id', $user->id)->where('status', 'PENDING')->sum('amount');
$failedTransfers = DB::table('transfers')->where('user_id', $user->id)->where('status', 'FAILED')->sum('amount');

echo "Successful Bank Transfers:                 ₦" . number_format($successTransfers, 2) . "\n";
echo "Pending Bank Transfers (In-flight):        ₦" . number_format($pendingTransfers, 2) . "\n";
echo "Failed Bank Transfers (Refunded):          ₦" . number_format($failedTransfers, 2) . "\n\n";

// 4. Utility / Purchase Spending Audit
echo "🛍️ [4] UTILITY SPENDING SUMS\n";
$airtimeSpend = DB::table('airtime')->where('username', $user->username)->where('plan_status', 1)->sum('amount');
$dataSpend = DB::table('data')->where('username', $user->username)->where('plan_status', 1)->sum('amount');
$billSpend = DB::table('bill')->where('username', $user->username)->where('plan_status', 1)->sum('amount');
$examSpend = DB::table('exam')->where('username', $user->username)->where('plan_status', 1)->sum('amount');

$totalSpending = $airtimeSpend + $dataSpend + $billSpend + $examSpend;
echo "Airtime:                                    ₦" . number_format($airtimeSpend, 2) . "\n";
echo "Data:                                       ₦" . number_format($dataSpend, 2) . "\n";
echo "Bills/Electricity:                          ₦" . number_format($billSpend, 2) . "\n";
echo "Exams:                                      ₦" . number_format($examSpend, 2) . "\n";
echo "--------------------------------------------------\n";
echo "TOTAL SYSTEM SPENDING:                      ₦" . number_format($totalSpending, 2) . "\n\n";

// 5. Total Balance Reconciliation Math
echo "📊 [5] RECONCILIATION SUMMARY\n";
echo "--------------------------------------------------\n";
echo "Inflow (Deposits):                          ₦" . number_format($totalInflow, 2) . "\n";
echo "Minus Outflows (Successful Transfers):     -₦" . number_format($successTransfers, 2) . "\n";
echo "Minus Outflows (Pending Transfers):        -₦" . number_format($pendingTransfers, 2) . "\n";
echo "Minus Outflows (Utility Spending):         -₦" . number_format($totalSpending, 2) . "\n";
echo "--------------------------------------------------\n";
$expectedBalance = $totalInflow - ($successTransfers + $pendingTransfers + $totalSpending);
echo "EXPECTED BALANCE:                           ₦" . number_format($expectedBalance, 2) . "\n";
echo "ACTUAL WALLET BALANCE:                      ₦" . number_format($user->bal, 2) . "\n";
$variance = $user->bal - $expectedBalance;
echo "VARIANCE (Difference):                      ₦" . number_format($variance, 2) . "\n";

if (abs($variance) < 0.01) {
    echo "✅ SUCCESS: Account balance reconciles perfectly with transaction logs.\n";
} else {
    echo "🚨 WARNING: Discrepancy detected! Balance does not match logs.\n";
}
echo "==================================================\n";
