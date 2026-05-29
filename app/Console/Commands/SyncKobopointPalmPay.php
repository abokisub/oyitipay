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
        $this->info("Checking for users who have a PalmPay virtual account but it's not mapped to user.palmpay...");

        // Get virtual accounts from pointwave_virtual_accounts that are PalmPay
        $virtualAccounts = DB::table('pointwave_virtual_accounts')
            ->where('bank_name', 'LIKE', '%PalmPay%')
            ->get();

        $count = 0;

        foreach ($virtualAccounts as $va) {
            $user = DB::table('user')->where('id', $va->user_id)->first();
            if ($user) {
                // If it's already the exact same account number, skip printing it to avoid spam
                if ($user->palmpay !== $va->account_number) {
                    DB::table('user')->where('id', $user->id)->update([
                        'palmpay' => $va->account_number
                    ]);
                    $this->info("✅ Updated PalmPay for user {$user->username} to {$va->account_number} (Replaced old account).");
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->info("Successfully synced $count missing PalmPay accounts from the local database!");
        } else {
            $this->info("All database users are already synced up.");
        }

        // Now, for any remaining organic users who have kobopoint_customer_id but NO virtual account and NO palmpay
        $remainingUsers = DB::table('user')
            ->whereNotNull('kobopoint_customer_id')
            ->whereNull('palmpay')
            ->get();

        if ($remainingUsers->isNotEmpty()) {
            $this->info("Fetching from API for " . $remainingUsers->count() . " remaining users...");
            foreach ($remainingUsers as $user) {
                $accountResult = $kobopointService->createVirtualAccount($user->kobopoint_customer_id, $user->name, 'static', ['033']);
                if ($accountResult['status'] === true && isset($accountResult['data']['bankAccounts'])) {
                    $palmpayAccount = null;
                    foreach ($accountResult['data']['bankAccounts'] as $acc) {
                        if ($acc['bankCode'] === '033' || $acc['bankCode'] === '100033' || stripos($acc['bankName'], 'PalmPay') !== false) {
                            $palmpayAccount = $acc['accountNumber'];
                            break;
                        }
                    }
                    if ($palmpayAccount) {
                        DB::table('user')->where('id', $user->id)->update(['palmpay' => $palmpayAccount]);
                        $this->info("✅ API Linked PalmPay $palmpayAccount to user {$user->username}");
                    }
                }
                sleep(1);
            }
        }

        $this->info("Sync complete!");
    }
}
