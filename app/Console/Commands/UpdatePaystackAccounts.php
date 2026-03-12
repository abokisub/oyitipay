<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class UpdatePaystackAccounts extends Command
{
    protected $signature = 'paystack:update-accounts';
    protected $description = 'Assign Paystack virtual accounts to all users who do not have one.';

    public function handle()
    {
        $controller = new Controller();
        $users = DB::table('user')->whereNull('paystack_account')->orWhere('paystack_account', '')->get();
        $bar = $this->output->createProgressBar(count($users));
        $bar->start();
        foreach ($users as $user) {
            try {
                $result = $controller->paystack_account($user->username);
                if (!$result) {
                    Log::error('Paystack account creation failed for user: ' . $user->username);
                }
            } catch (\Exception $e) {
                Log::error('Exception for user ' . $user->username . ': ' . $e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->info("\nPaystack account assignment complete. Check logs for any errors.");
    }
} 