<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\KobopointService;

class SyncKobopointPalmPay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kobopoint:sync-palmpay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync missing PalmPay accounts from Kobopoint for users with a customer ID';

    /**
     * Execute the console command.
     */
    public function handle(KobopointService $kobopointService)
    {
        $users = DB::table('user')
            ->whereNotNull('kobopoint_customer_id')
            ->whereNull('palmpay')
            ->get();

        if ($users->isEmpty()) {
            $this->info("No users found with missing PalmPay accounts.");
            return;
        }

        $this->info("Found " . $users->count() . " users missing PalmPay accounts. Starting sync...");

        foreach ($users as $user) {
            $this->info("Syncing user ID: {$user->id} ({$user->username}) - Customer ID: {$user->kobopoint_customer_id}");

            // Call createVirtualAccount on the existing customer.
            // Kobopoint will either return the existing one or create it.
            $accountResult = $kobopointService->createVirtualAccount($user->kobopoint_customer_id, $user->name, 'static', ['033']);

            if ($accountResult['status'] === true && isset($accountResult['data']['bankAccounts'])) {
                $virtualAccounts = $accountResult['data']['bankAccounts'];
                $palmpayAccount = null;

                foreach ($virtualAccounts as $acc) {
                    if ($acc['bankCode'] === '033' || $acc['bankCode'] === '100033' || stripos($acc['bankName'], 'PalmPay') !== false) {
                        $palmpayAccount = $acc['accountNumber'];
                        break;
                    }
                }

                if ($palmpayAccount) {
                    DB::table('user')->where('id', $user->id)->update(['palmpay' => $palmpayAccount]);
                    $this->info("✅ Successfully linked PalmPay $palmpayAccount to user {$user->username}");
                } else {
                    $this->error("❌ No PalmPay account found in response for {$user->username}");
                }
            } else {
                $this->error("❌ Failed to fetch virtual accounts for {$user->username}.");
                $this->error("Response: " . json_encode($accountResult));
            }
            
            // Add a small delay to avoid hitting rate limits
            sleep(1);
        }

        $this->info("Sync complete!");
    }
}
