<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Read username from CLI argument, default to 'oshafu'
$targetUsername = isset($argv[1]) ? trim($argv[1]) : 'oshafu';

echo "====================================================================\n";
echo "🚨 OYITIPAY USER AUDIT REPORT: " . strtoupper($targetUsername) . "\n";
echo "====================================================================\n\n";

$user = User::where('username', $targetUsername)->first();

if (!$user) {
    echo "❌ User '$targetUsername' NOT found in the database!\n";
    $matches = User::where('username', 'like', "%$targetUsername%")
                   ->orWhere('name', 'like', "%$targetUsername%")
                   ->limit(10)
                   ->get();
    if ($matches->count() > 0) {
        echo "\nDid you mean one of these users?\n";
        foreach ($matches as $m) {
            echo "- Username: {$m->username} | Name: {$m->name} | Email: {$m->email}\n";
        }
    }
    exit(1);
}

// 1. User Profile Details
echo "👤 [1] USER PROFILE DETAILS\n";
echo "--------------------------------------------------------------------\n";
echo "ID:             " . $user->id . "\n";
echo "Username:       " . $user->username . "\n";
echo "Name:           " . $user->name . "\n";
echo "Email:          " . $user->email . "\n";
echo "Phone:          " . $user->phone . "\n";
echo "Balance:        ₦" . number_format($user->bal, 2) . "\n";
echo "Status:         " . ($user->status == 1 ? "Active (1)" : "Banned/Locked ({$user->status})") . "\n";
echo "KYC Status:     " . ($user->kyc_status ?? 'N/A') . "\n";
echo "Registered:     " . ($user->date ?? 'N/A') . "\n";
echo "BVN:            " . ($user->bvn ?? 'Not linked') . "\n";
echo "NIN:            " . ($user->nin ?? 'Not linked') . "\n";
echo "\n";

// 2. Detect Linked Accounts / Duplicate BVN or NIN
$bvn = $user->bvn;
$nin = $user->nin;
$linkedAccounts = collect();

echo "🔗 [2] LINKED ACCOUNTS & SHARED IDENTITY DETECTOR\n";
echo "--------------------------------------------------------------------\n";

if ($bvn) {
    $byBvn = User::where('bvn', $bvn)->where('id', '!=', $user->id)->get();
    if ($byBvn->count() > 0) {
        echo "🚨 Found " . $byBvn->count() . " other account(s) sharing BVN ($bvn):\n";
        foreach ($byBvn as $linked) {
            echo "  - Username: {$linked->username} | ID: {$linked->id} | Email: {$linked->email} | Status: {$linked->status} | Bal: ₦" . number_format($linked->bal, 2) . "\n";
            $linkedAccounts->push($linked);
        }
    } else {
        echo "✅ No other accounts share this user's BVN ($bvn).\n";
    }
} else {
    echo "⚠️ No BVN linked to this user's account to search duplicates.\n";
}

if ($nin) {
    $byNin = User::where('nin', $nin)->where('id', '!=', $user->id)->get();
    if ($byNin->count() > 0) {
        echo "🚨 Found " . $byNin->count() . " other account(s) sharing NIN ($nin):\n";
        foreach ($byNin as $linked) {
            echo "  - Username: {$linked->username} | ID: {$linked->id} | Email: {$linked->email} | Status: {$linked->status} | Bal: ₦" . number_format($linked->bal, 2) . "\n";
            $linkedAccounts->push($linked);
        }
    } else {
        echo "✅ No other accounts share this user's NIN ($nin).\n";
    }
} else {
    echo "⚠️ No NIN linked to this user's account to search duplicates.\n";
}

// Check if any other users have matching phone or email variations
$byPhone = User::where('phone', $user->phone)->where('id', '!=', $user->id)->get();
if ($byPhone->count() > 0) {
    echo "🚨 Found " . $byPhone->count() . " account(s) sharing the exact same Phone Number:\n";
    foreach ($byPhone as $linked) {
        echo "  - Username: {$linked->username} | ID: {$linked->id} | Status: {$linked->status} | Bal: ₦" . number_format($linked->bal, 2) . "\n";
        $linkedAccounts->push($linked);
    }
}

$linkedAccounts = $linkedAccounts->unique('id');
echo "Total Unique Linked / Duplicate Accounts Found: " . $linkedAccounts->count() . "\n\n";

// Helper function to calculate funding and spending
function getStatsForUser($username, $userId) {
    $funding = DB::table('deposit')
        ->where('username', $username)
        ->where('status', 1)
        ->sum('amount');

    $transfersOut = DB::table('transfers')
        ->where('user_id', $userId)
        ->where('status', 'SUCCESS')
        ->sum('amount');

    $airtimeSpend = DB::table('airtime')
        ->where('username', $username)
        ->where('plan_status', 1)
        ->sum('amount');

    $dataSpend = DB::table('data')
        ->where('username', $username)
        ->where('plan_status', 1)
        ->sum('amount');

    $billSpend = DB::table('bill')
        ->where('username', $username)
        ->where('plan_status', 1)
        ->sum('amount');

    $examSpend = DB::table('exam')
        ->where('username', $username)
        ->where('plan_status', 1)
        ->sum('amount');

    $totalSpend = $airtimeSpend + $dataSpend + $billSpend + $examSpend;

    return [
        'funding' => $funding,
        'transfers' => $transfersOut,
        'airtime' => $airtimeSpend,
        'data' => $dataSpend,
        'bill' => $billSpend,
        'exam' => $examSpend,
        'total_spend' => $totalSpend
    ];
}

// 3. Transactions & Totals
echo "💰 [3] FUNDING & SPENDING SUMMARY FOR " . strtoupper($user->username) . "\n";
echo "--------------------------------------------------------------------\n";
$stats = getStatsForUser($user->username, $user->id);
echo "Total Funding IN (Successful Deposits):         ₦" . number_format($stats['funding'], 2) . "\n";
echo "Total Transfers OUT (Successful Transfers):     ₦" . number_format($stats['transfers'], 2) . "\n";
echo "Total Airtime Spending:                         ₦" . number_format($stats['airtime'], 2) . "\n";
echo "Total Data Spending:                            ₦" . number_format($stats['data'], 2) . "\n";
echo "Total Cable/Bill Spending:                      ₦" . number_format($stats['bill'], 2) . "\n";
echo "Total Exam Spending:                            ₦" . number_format($stats['exam'], 2) . "\n";
echo "--------------------------------------------------------------------\n";
echo "TOTAL SYSTEM SPENDING (Airtime+Data+Bills+Exams): ₦" . number_format($stats['total_spend'], 2) . "\n";
echo "TOTAL OUTFLOW (Transfers + Spending):           ₦" . number_format($stats['transfers'] + $stats['total_spend'], 2) . "\n";
echo "\n";

// 4. Detailed Deposit (Funding) History
echo "📥 [4] DETAILED DEPOSIT (FUNDING) HISTORY FOR " . strtoupper($user->username) . "\n";
echo "--------------------------------------------------------------------\n";
$deposits = DB::table('deposit')
    ->where('username', $user->username)
    ->orderBy('id', 'desc')
    ->limit(30)
    ->get();

if ($deposits->count() > 0) {
    foreach ($deposits as $d) {
        $statusStr = $d->status == 1 ? "SUCCESS" : "FAILED/PENDING ({$d->status})";
        echo "- Date:      " . ($d->date ?? 'N/A') . "\n";
        echo "  Amount:    ₦" . number_format($d->amount, 2) . " (Charges: ₦" . number_format($d->charges, 2) . ")\n";
        echo "  Bal Change:₦" . number_format($d->oldbal, 2) . " ➔ ₦" . number_format($d->newbal, 2) . "\n";
        echo "  Method:    " . ($d->type ?? 'N/A') . " (Wallet: " . ($d->wallet_type ?? 'N/A') . ")\n";
        echo "  Credit By: " . ($d->credit_by ?? 'N/A') . "\n";
        echo "  Trans ID:  " . ($d->transid ?? 'N/A') . " (Monnify Ref: " . ($d->monify_ref ?? 'N/A') . ")\n";
        echo "  Status:    " . $statusStr . "\n";
        echo "  --------------------------------------------------\n";
    }
} else {
    echo "⚠️ No deposit records found for this user in 'deposit' table.\n";
    
    $messages = DB::table('message')
        ->where('username', $user->username)
        ->where(function($q) {
            $q->where('role', 'credit')->orWhere('title', 'like', '%credit%')->orWhere('message', 'like', '%credit%');
        })
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    if ($messages->count() > 0) {
        echo "\n🚨 Potential Manual Credits/Logs in 'message' table:\n";
        foreach ($messages as $msg) {
            echo "- Date: {$msg->habukhan_date} | Title: {$msg->title} | Amt: ₦{$msg->amount} | Role: {$msg->role} | Text: {$msg->message}\n";
        }
    }
}
echo "\n";

// 5. Recent Transaction / Spending Logs
echo "💸 [5] RECENT OUTFLOW TRANSACTION LOGS (LAST 30)\n";
echo "--------------------------------------------------------------------\n";

$outflows = collect();

// Fetch Airtime
$airtimes = DB::table('airtime')->where('username', $user->username)->orderBy('id', 'desc')->limit(30)->get()->map(function($item) {
    return [
        'date' => $item->plan_date,
        'type' => 'Airtime (' . strtoupper($item->network) . ')',
        'details' => $item->plan_phone,
        'amount' => $item->amount,
        'status' => $item->plan_status == 1 ? 'SUCCESS' : 'FAILED/PENDING (' . $item->plan_status . ')',
        'transid' => $item->transid
    ];
});
$outflows = $outflows->concat($airtimes);

// Fetch Data
$datas = DB::table('data')->where('username', $user->username)->orderBy('id', 'desc')->limit(30)->get()->map(function($item) {
    return [
        'date' => $item->plan_date,
        'type' => 'Data (' . strtoupper($item->network) . ' ' . $item->plan_name . ')',
        'details' => $item->plan_phone,
        'amount' => $item->amount,
        'status' => $item->plan_status == 1 ? 'SUCCESS' : 'FAILED/PENDING (' . $item->plan_status . ')',
        'transid' => $item->transid
    ];
});
$outflows = $outflows->concat($datas);

// Fetch Bills
$bills = DB::table('bill')->where('username', $user->username)->orderBy('id', 'desc')->limit(30)->get()->map(function($item) {
    return [
        'date' => $item->plan_date,
        'type' => 'Bill (' . $item->disco_name . ')',
        'details' => 'Meter: ' . $item->meter_number,
        'amount' => $item->amount,
        'status' => $item->plan_status == 1 ? 'SUCCESS' : 'FAILED/PENDING (' . $item->plan_status . ')',
        'transid' => $item->transid
    ];
});
$outflows = $outflows->concat($bills);

// Fetch Exams
$exams = DB::table('exam')->where('username', $user->username)->orderBy('id', 'desc')->limit(30)->get()->map(function($item) {
    return [
        'date' => $item->plan_date,
        'type' => 'Exam (' . $item->exam_name . ')',
        'details' => 'Qty: ' . $item->quantity,
        'amount' => $item->amount,
        'status' => $item->plan_status == 1 ? 'SUCCESS' : 'FAILED/PENDING (' . $item->plan_status . ')',
        'transid' => $item->transid
    ];
});
$outflows = $outflows->concat($exams);

// Fetch Transfers
$transfers = DB::table('transfers')->where('user_id', $user->id)->orderBy('id', 'desc')->limit(30)->get()->map(function($item) {
    return [
        'date' => $item->created_at,
        'type' => 'Bank Transfer (' . $item->bank_name . ')',
        'details' => $item->account_number . ' - ' . $item->account_name,
        'amount' => $item->amount,
        'status' => $item->status,
        'transid' => $item->reference
    ];
});
$outflows = $outflows->concat($transfers);

// Sort by date desc
$sortedOutflows = $outflows->sortByDesc('date')->take(30);

if ($sortedOutflows->count() > 0) {
    foreach ($sortedOutflows as $o) {
        echo "- Date:    " . ($o['date'] ?? 'N/A') . "\n";
        echo "  Type:    " . $o['type'] . "\n";
        echo "  Details: " . $o['details'] . "\n";
        echo "  Amount:  ₦" . number_format($o['amount'], 2) . "\n";
        echo "  Trans ID:" . $o['transid'] . "\n";
        echo "  Status:  " . $o['status'] . "\n";
        echo "  --------------------------------------------------\n";
    }
} else {
    echo "ℹ️ No spending transactions found for this user.\n";
}
echo "\n";

// 6. Related Accounts Stats
if ($linkedAccounts->count() > 0) {
    echo "👥 [6] STATS FOR DETECTED LINKED ACCOUNTS\n";
    echo "--------------------------------------------------------------------\n";
    foreach ($linkedAccounts as $linked) {
        $lStats = getStatsForUser($linked->username, $linked->id);
        echo "👉 Account: {$linked->username} (Status: {$linked->status})\n";
        echo "   - Balance:             ₦" . number_format($linked->bal, 2) . "\n";
        echo "   - Funding IN:          ₦" . number_format($lStats['funding'], 2) . "\n";
        echo "   - Transfers OUT:       ₦" . number_format($lStats['transfers'], 2) . "\n";
        echo "   - Utility Spending:    ₦" . number_format($lStats['total_spend'], 2) . "\n";
        echo "   - Total Outflow:       ₦" . number_format($lStats['transfers'] + $lStats['total_spend'], 2) . "\n";
        echo "\n";
    }
}
echo "====================================================================\n";
