<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearTransactionHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kobopoint:clear-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all transaction history and reset user balances for a fresh installation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->confirm('This will PERMANENTLY DELETE all transaction history and RESET user balances. Do you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting database cleanup...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'airtime', 'data', 'cable', 'bill', 'bulksms', 'exam', 'result_charge',
            'message',
            'transfers', 'bank_transfer', 'wallet_funding', 'deposit',
            'virtual_cards', 'card_transactions',
            'notif', 'notifications', 'notification_broadcasts',
            'support_tickets', 'support_messages',
            'beneficiaries', 'service_beneficiaries',
            'request'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("Truncating table: $table");
                DB::table($table)->truncate();
            }
            else {
                $this->warn("Table not found: $table (Skipping)");
            }
        }

        $this->info('Resetting user balances...');
        DB::table('user')->update([
            'bal' => 0,
            'refbal' => 0
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Transaction history cleared and balances reset successfully.');

        return 0;
    }
}