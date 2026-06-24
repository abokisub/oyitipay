<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AuditBannedUsers extends Command
{
    protected $signature = 'audit:banned';
    protected $description = 'List all banned/restricted users and their balances';

    public function handle()
    {
        $this->info("Fetching banned/restricted users (status != 1)...");

        $bannedUsers = User::where('status', '!=', 1)
            ->select('id', 'username', 'name', 'email', 'status', 'bal')
            ->orderBy('bal', 'desc')
            ->get();

        if ($bannedUsers->isEmpty()) {
            $this->warn("No banned or restricted users found.");
            return 0;
        }

        $headers = ['ID', 'Username', 'Name', 'Email', 'Status', 'Balance (₦)'];
        $rows = [];
        $totalBalance = 0;

        foreach ($bannedUsers as $user) {
            $totalBalance += $user->bal;
            $rows[] = [
                $user->id,
                $user->username,
                $user->name,
                $user->email,
                $user->status,
                number_format($user->bal, 2)
            ];
        }

        $this->table($headers, $rows);

        $this->info("\n==================================================");
        $this->info("Total Accounts Locked:   " . $bannedUsers->count());
        $this->info("Total Locked Balance:    ₦" . number_format($totalBalance, 2));
        $this->info("==================================================");

        return 0;
    }
}
