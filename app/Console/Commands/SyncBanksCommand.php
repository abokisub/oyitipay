<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Banking\BankingService;
use Illuminate\Support\Facades\Log;

class SyncBanksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banks:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Unified Bank List from configured providers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(BankingService $bankingService)
    {
        $this->info('Starting Bank Sync...');

        // 1. Sync Paystack (Primary Source)
        try {
            $this->info('Syncing Paystack...');
            $count = $bankingService->syncBanksFromProvider('paystack');
            $this->info("Synced $count banks from Paystack.");
        } catch (\Exception $e) {
            $this->error('Paystack Sync Failed: ' . $e->getMessage());
            Log::error('Paystack Sync Failed: ' . $e->getMessage());
        }

        // 2. Sync Xixapay (Optional/Fallback)
        try {
            $this->info('Syncing Xixapay...');
            $countXi = $bankingService->syncBanksFromProvider('xixapay');
            $this->info("Synced $countXi banks from Xixapay.");
        } catch (\Exception $e) {
            $this->error('Xixapay Sync Failed: ' . $e->getMessage());
            Log::error('Xixapay Sync Failed: ' . $e->getMessage());
        }

        $this->info('Bank Sync Process Completed.');
        return 0;
    }
}
