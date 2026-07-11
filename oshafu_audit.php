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
echo "🔍 DETAILED FRAUD AUDIT: " . strtoupper($user->username) . " (ID: {$user->id})\n";
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
echo "Reason:        " . ($user->reason ?? 'N/A') . "\n";
echo "Balance:       ₦" . number_format($user->bal, 2) . "\n";
echo "Device ID:     " . ($user->device_id ?? 'N/A') . "\n";
echo "App Key:       {$user->app_key}\n";
echo "API Key:       {$user->apikey}\n";
echo "Habukhan Key:  {$user->habukhan_key}\n\n";

// 2. All Virtual Accounts Created For This User
echo "💳 [2] VIRTUAL ACCOUNTS CREATED\n";
if (Schema::hasTable('user_bank')) {
    $banks = DB::table('user_bank')->where('username', $user->username)->get();
    foreach ($banks as $b) {
        $bArr = (array)$b;
        $bankName = $bArr['bank'] ?? 'N/A';
        $accNo = $bArr['account_number'] ?? 'N/A';
        $accName = $bArr['account_name'] ?? ($bArr['name'] ?? 'N/A');
        echo "  - Bank: {$bankName} | Account Name: {$accName} | Account Number: {$accNo}\n";
    }
}
if (Schema::hasTable('pointwave_virtual_accounts')) {
    $pva = DB::table('pointwave_virtual_accounts')->where('user_id', $user->id)->get();
    foreach ($pva as $p) {
        echo "  - PointWave virtual account: Bank: {$p->bank_name} | Acc No: {$p->account_number} | Customer ID: {$p->customer_id}\n";
    }
}
echo "\n";

// 3. Search for Multiple/Linked Accounts (Shared Device, BVN, Phone, Email, IP)
echo "🔗 [3] MULTIPLE / RELATED ACCOUNTS DETECTED\n";
$linked = collect();

if ($user->bvn) {
    $byBvn = User::where('bvn', $user->bvn)->where('id', '!=', $user->id)->get();
    foreach ($byBvn as $u) {
        echo "  - Shared BVN: ID: {$u->id} | Username: {$u->username} | Name: {$u->name} | Phone: {$u->phone} | Status: {$u->status}\n";
        $linked->push($u);
    }
}
if ($user->device_id) {
    $byDevice = User::where('device_id', $user->device_id)->where('id', '!=', $user->id)->get();
    foreach ($byDevice as $u) {
        echo "  - Shared Device: ID: {$u->id} | Username: {$u->username} | Name: {$u->name} | Phone: {$u->phone} | Status: {$u->status}\n";
        $linked->push($u);
    }
}
$byPhone = User::where('phone', $user->phone)->where('id', '!=', $user->id)->get();
foreach ($byPhone as $u) {
    echo "  - Shared Phone: ID: {$u->id} | Username: {$u->username} | Name: {$u->name} | Status: {$u->status}\n";
    $linked->push($u);
}

$byEmail = User::where('email', $user->email)->where('id', '!=', $user->id)->get();
foreach ($byEmail as $u) {
    echo "  - Shared Email: ID: {$u->id} | Username: {$u->username} | Name: {$u->name} | Status: {$u->status}\n";
    $linked->push($u);
}

if ($linked->unique('id')->count() == 0) {
    echo "  ✅ No linked/multiple accounts detected sharing their BVN, Device ID, Phone, or Email.\n";
}
echo "\n";

// 4. Trace Balance Anomalies (Where did the balance come from?)
echo "💰 [4] BALANCE SOURCE ANOMALY INVESTIGATION\n";
// Let's get ALL successful deposits
$successDeposits = DB::table('deposit')->where('username', $user->username)->where('status', 1)->sum('amount');
// Let's get all deposits (regardless of status) to see if they had pending/failed ones
$allDepositsCount = DB::table('deposit')->where('username', $user->username)->count();
$allDepositsSum = DB::table('deposit')->where('username', $user->username)->sum('amount');

echo "Total Successful Deposits (deposit table): ₦" . number_format($successDeposits, 2) . "\n";
echo "Total Deposit Entries in DB (All status):  " . $allDepositsCount . " (Sum: ₦" . number_format($allDepositsSum, 2) . ")\n";

// Check if they were credited through other tables e.g. PointWave
if (Schema::hasTable('pointwave_transactions')) {
    $pwCredits = DB::table('pointwave_transactions')
        ->where('user_id', $user->id)
        ->where('status', 'success')
        ->sum('amount');
    echo "Total Successful PointWave Credits:        ₦" . number_format($pwCredits, 2) . "\n";
}

// Check for Webhooks containing the username
if (Schema::hasTable('webhook_events')) {
    $whCount = DB::table('webhook_events')->where('payload', 'like', "%{$user->username}%")->count();
    echo "Total Webhook Events for username:         " . $whCount . "\n";
}

// Trace if any refund was done (i.e. failed transaction refunded balance incorrectly)
// Let's check for failed transactions that refunded them
$failedDataRefunds = DB::table('data')
    ->where('username', $user->username)
    ->where('plan_status', 2)
    ->sum('amount');

$failedAirtimeRefunds = DB::table('airtime')
    ->where('username', $user->username)
    ->where('plan_status', 2)
    ->sum('amount');

$failedTransfersRefunds = DB::table('transfers')
    ->where('user_id', $user->id)
    ->where('status', 'FAILED')
    ->sum('amount');

echo "\n--- FAILED TRANSACTIONS (REFUND TRACE) ---\n";
echo "Total Failed Data Refunds:                ₦" . number_format($failedDataRefunds, 2) . "\n";
echo "Total Failed Airtime Refunds:             ₦" . number_format($failedAirtimeRefunds, 2) . "\n";
echo "Total Failed Bank Transfer Refunds:       ₦" . number_format($failedTransfersRefunds, 2) . "\n";

// 5. Raw deposits dump
echo "\n📥 [5] RAW DEPOSITS RECORD DUMP\n";
$depositsList = DB::table('deposit')->where('username', $user->username)->orderBy('id', 'desc')->get();
if ($depositsList->count() > 0) {
    foreach ($depositsList as $d) {
        echo "  - ID: {$d->id} | Date: {$d->date} | Amt: ₦" . number_format($d->amount, 2) . " | Status: {$d->status} | Credit By: {$d->credit_by} | Type: {$d->type} | Ref: {$d->transid}\n";
    }
} else {
    echo "  No deposit entries found.\n";
}

echo "\n==================================================\n";
