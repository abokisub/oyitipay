<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FlushBannedBalances extends Command
{
    protected $signature = 'user:flush-banned';
    protected $description = 'Set the balance of all banned and restricted users (status != 1) to 0';

    public function handle()
    {
        $this->info("Calculating total balances to be flushed...");

        $bannedUsers = User::where('status', '!=', 1)->get();
        $count = $bannedUsers->count();
        $totalBalance = $bannedUsers->sum('bal');

        if ($count === 0) {
            $this->warn("No banned/restricted users found to flush.");
            return 0;
        }

        $this->warn("You are about to flush the balances of $count banned/restricted accounts.");
        $this->warn("Total balance to be set to 0: ₦" . number_format($totalBalance, 2));

        if ($this->confirm('Are you absolutely sure you want to permanently clear these balances to 0?')) {
            DB::beginTransaction();
            try {
                // Update all user balances where status is not 1 to 0
                User::where('status', '!=', 1)->update(['bal' => 0]);
                
                // Write a log in the message table for records (optional)
                foreach ($bannedUsers as $user) {
                    if ($user->bal > 0) {
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'message' => "Wallet balance of ₦" . number_format($user->bal, 2) . " permanently flushed/deducted by Admin.",
                            'amount' => $user->bal,
                            'oldbal' => $user->bal,
                            'newbal' => 0,
                            'role' => 'transfer',
                            'plan_status' => 1,
                            'habukhan_date' => now(),
                            'transid' => 'FLUSH_' . uniqid(),
                        ]);
                    }
                }

                DB::commit();
                $this->info("Successfully flushed ₦" . number_format($totalBalance, 2) . " across $count banned accounts to ₦0.00.");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error flushing balances: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->info("Operation cancelled.");
        }

        return 0;
    }
}
