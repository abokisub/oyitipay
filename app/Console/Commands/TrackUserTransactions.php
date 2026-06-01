<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrackUserTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:track-user {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Track all transactions (deposits, internal transfers, external transfers) for a specific user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $username = $this->argument('username');
        $user = DB::table('user')->where('username', $username)->first();

        if (!$user) {
            $this->error("User {$username} not found.");
            return 1;
        }

        $this->info("===================================================================");
        $this->info(" TRACKING TRANSACTIONS FOR: {$username} (Name: {$user->name})");
        $this->info("===================================================================");

        // 1. Funding / Deposits
        $this->info("\n[1] EXTERNAL DEPOSITS / FUNDING (Recent 10)");
        $deposits = DB::table('deposit')
            ->where('username', $username)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        $depositHeaders = ['Date', 'Type', 'Amount', 'Old Bal', 'New Bal', 'Ref/Status'];
        $depositData = [];
        foreach ($deposits as $d) {
            $depositData[] = [
                $d->date ?? 'N/A', 
                $d->type ?? 'N/A', 
                $d->amount ?? 0, 
                $d->oldbal ?? 0, 
                $d->newbal ?? 0, 
                ($d->transid ?? 'N/A') . ' (Status: ' . ($d->status ?? 'N/A') . ')'
            ];
        }
        if (count($depositData) > 0) {
            $this->table($depositHeaders, $depositData);
        } else {
            $this->warn("No deposits found.");
        }

        // 2. Internal Transfers (Sent)
        $this->info("\n[2] INTERNAL TRANSFERS - SENT (Recent 10)");
        $internalSent = DB::table('message')
            ->where('username', $username)
            ->where('role', 'transfer_sent')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $intSentHeaders = ['Date', 'Recipient', 'Amount', 'Old Bal', 'New Bal', 'Message'];
        $intSentData = [];
        foreach ($internalSent as $m) {
            // Extract recipient from message string (e.g. "Wallet transfer of 500 to Habukhan")
            $recipient = 'Unknown';
            if (preg_match('/to\s+([a-zA-Z0-9_]+)$/i', $m->message, $matches)) {
                $recipient = $matches[1];
            }

            $intSentData[] = [
                $m->habukhan_date ?? $m->date ?? 'N/A', 
                $recipient, 
                $m->amount ?? 0, 
                $m->oldbal ?? 0, 
                $m->newbal ?? 0, 
                $m->message ?? ''
            ];
        }
        if (count($intSentData) > 0) {
            $this->table($intSentHeaders, $intSentData);
        } else {
            $this->warn("No internal transfers sent.");
        }

        // 3. Internal Transfers (Received)
        $this->info("\n[3] INTERNAL TRANSFERS - RECEIVED (Recent 10)");
        // Look for messages where someone sent to this user
        $internalReceived = DB::table('message')
            ->where('role', 'transfer_sent')
            ->where('message', 'like', "%to {$username}")
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
            
        $intRecvHeaders = ['Date', 'Sender', 'Amount', 'Old Bal', 'New Bal', 'Message'];
        $intRecvData = [];
        foreach ($internalReceived as $m) {
            $intRecvData[] = [
                $m->habukhan_date ?? $m->date ?? 'N/A', 
                $m->username ?? 'Unknown', 
                $m->amount ?? 0, 
                $m->oldbal ?? 0, 
                $m->newbal ?? 0, 
                $m->message ?? ''
            ];
        }
        if (count($intRecvData) > 0) {
            $this->table($intRecvHeaders, $intRecvData);
        } else {
            $this->warn("No internal transfers received.");
        }

        // 4. External Bank Transfers (Withdrawals)
        $this->info("\n[4] EXTERNAL BANK TRANSFERS - WITHDRAWALS (Recent 10)");
        $withdrawals = DB::table('transfers')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $wdHeaders = ['Date', 'Amount', 'Bank', 'Account Number', 'Account Name', 'Status'];
        $wdData = [];
        foreach ($withdrawals as $w) {
            $wdData[] = [
                $w->created_at ?? $w->date ?? 'N/A', 
                $w->amount ?? 0, 
                $w->bank_name ?? 'N/A', 
                $w->account_number ?? 'N/A', 
                $w->account_name ?? 'N/A', 
                $w->status ?? 'N/A'
            ];
        }
        if (count($wdData) > 0) {
            $this->table($wdHeaders, $wdData);
        } else {
            $this->warn("No external bank transfers found.");
        }

        $this->info("\nDone tracking {$username}.\n");

        return 0;
    }
}
