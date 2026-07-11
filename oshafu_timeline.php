<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$username = 'oshafu';

echo "==================================================\n";
echo "🔍 DETAILED BALANCE TIMELINE FOR USERNAME: " . strtoupper($username) . "\n";
echo "==================================================\n\n";

if (Illuminate\Support\Facades\Schema::hasTable('message')) {
    $messages = DB::table('message')
        ->where('username', $username)
        ->orderBy('id', 'asc') // chronological order
        ->get();

    echo "Found " . $messages->count() . " ledger history records in message table:\n\n";
    foreach ($messages as $m) {
        $date = $m->habukhan_date ?? $m->date ?? 'N/A';
        $role = $m->role ?? 'N/A';
        $amount = $m->amount ?? 0;
        $oldBal = $m->oldbal ?? 0;
        $newBal = $m->newbal ?? 0;
        $desc = $m->message ?? 'No description';
        $ref = $m->transid ?? 'N/A';
        
        echo "[$date] Role: $role | Amt: ₦" . number_format($amount, 2) . " | Bal: ₦" . number_format($oldBal, 2) . " -> ₦" . number_format($newBal, 2) . " | Ref: $ref\n";
        echo "  Desc: $desc\n";
        echo "--------------------------------------------------\n";
    }
} else {
    echo "message table not found.\n";
}
