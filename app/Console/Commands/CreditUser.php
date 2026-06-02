<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditUser extends Command
{
    protected $signature = 'user:credit {username} {amount}';
    protected $description = 'Manually credit a user account';

    public function handle()
    {
        $username = $this->argument('username');
        $amount = (float) $this->argument('amount');

        $user = DB::table('user')->where('username', $username)->first();

        if (!$user) {
            $this->error("User not found: " . $username);
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('user')->where('id', $user->id)->update(['bal' => $user->bal + $amount]);

            // Add an entry in the message table to show up in transactions history
            DB::table('message')->insert([
                'username' => $user->username,
                'message' => "Manual Credit: Wallet Funded via Admin (Missing Deposit Recovery)",
                'amount' => $amount,
                'oldbal' => $user->bal,
                'newbal' => $user->bal + $amount,
                'type' => 'credit',
                'status' => 'success',
                'created_at' => now(),
            ]);

            DB::commit();
            $this->info("Successfully credited $username with NGN $amount. Old Balance: {$user->bal}, New Balance: " . ($user->bal + $amount));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to credit user: " . $e->getMessage());
        }
    }
}
