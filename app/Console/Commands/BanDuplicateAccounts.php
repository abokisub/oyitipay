<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BanDuplicateAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ban-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans for duplicate accounts by device_id or name and auto-bans the newer ones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning for duplicate accounts...");
        $bannedCount = 0;

        // 1. Scan by device_id
        $this->info("Scanning by device_id...");
        $duplicateDevices = DB::table('user')
            ->select('device_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device_id')
            ->where('device_id', '!=', '')
            ->whereIn('status', [1]) // Only look at active accounts
            ->groupBy('device_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateDevices as $dup) {
            // Get all accounts for this device_id ordered by oldest first
            $accounts = DB::table('user')
                ->where('device_id', $dup->device_id)
                ->whereIn('status', [1])
                ->orderBy('id', 'asc')
                ->get();

            $originalAccount = $accounts->first();
            $duplicates = $accounts->slice(1); // All except the first one

            foreach ($duplicates as $duplicate) {
                DB::table('user')->where('id', $duplicate->id)->update([
                    'status' => 2,
                    'reason' => 'Duplicate Account Detected (Matched device_id with ' . $originalAccount->username . ')'
                ]);
                $this->warn("Banned duplicate device_id account: {$duplicate->username} (Matched with {$originalAccount->username})");
                $bannedCount++;
            }
        }

        // 2. Scan by Exact Full Name
        $this->info("Scanning by exact name...");
        $duplicateNames = DB::table('user')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereIn('status', [1])
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateNames as $dup) {
            // Check if name is generic (like "User" or "Test") to avoid false positives
            if (strlen(trim($dup->name)) < 5 || strtolower(trim($dup->name)) == 'test account' || strtolower(trim($dup->name)) == 'admin') {
                continue;
            }

            $accounts = DB::table('user')
                ->where('name', $dup->name)
                ->whereIn('status', [1])
                ->orderBy('id', 'asc')
                ->get();

            $originalAccount = $accounts->first();
            $duplicates = $accounts->slice(1);

            foreach ($duplicates as $duplicate) {
                DB::table('user')->where('id', $duplicate->id)->update([
                    'status' => 2,
                    'reason' => 'Duplicate Account Detected (Matched full name with ' . $originalAccount->username . ')'
                ]);
                $this->warn("Banned duplicate name account: {$duplicate->username} (Matched with {$originalAccount->username})");
                $bannedCount++;
            }
        }

        $this->info("Scan complete. Total accounts banned: {$bannedCount}");
    }
}
