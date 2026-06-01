<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrackUserTransactions extends Command
{
    protected $signature = 'app:track-user {username}';
    protected $description = 'Track all transactions for a specific user';

    public function handle()
    {
        $username = $this->argument('username');
        $user = DB::table('user')->where('username', $username)->first();

        if (!$user) {
            $this->error("User {$username} not found.");
            return 1;
        }

        $this->info("===================================================================");
        $this->info(" FULL LEDGER (MESSAGE TABLE) FOR: {$username}");
        $this->info("===================================================================");

        $messages = DB::table('message')
            ->where('username', $username)
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        if (count($messages) == 0) {
            $this->warn("No transaction history found in messages.");
        } else {
            $headers = ['ID', 'Date', 'Role', 'Amount', 'Old Bal', 'New Bal', 'Message (Truncated)'];
            $data = [];
            foreach ($messages as $m) {
                $msgText = substr($m->message ?? '', 0, 50) . (strlen($m->message ?? '') > 50 ? '...' : '');
                $msgText = str_replace("\n", " ", $msgText);
                $data[] = [
                    $m->id,
                    $m->habukhan_date ?? $m->date ?? 'N/A',
                    $m->role ?? 'N/A',
                    $m->amount ?? 0,
                    $m->oldbal ?? 0,
                    $m->newbal ?? 0,
                    $msgText
                ];
            }
            $this->table($headers, $data);
        }

        $this->info("===================================================================");
        $this->info(" ALL DEPOSITS FOR: {$username}");
        $this->info("===================================================================");
        
        $deposits = DB::table('deposit')
            ->where('username', $username)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
            
        if (count($deposits) > 0) {
            $depHeaders = ['ID', 'Date', 'Type', 'Amount', 'Old Bal', 'New Bal', 'Ref/Status'];
            $depData = [];
            foreach ($deposits as $d) {
                $depData[] = [
                    $d->id,
                    $d->date ?? 'N/A', 
                    $d->type ?? 'N/A', 
                    $d->amount ?? 0, 
                    $d->oldbal ?? 0, 
                    $d->newbal ?? 0, 
                    ($d->transid ?? 'N/A') . ' (Status: ' . ($d->status ?? 'N/A') . ')'
                ];
            }
            $this->table($depHeaders, $depData);
        } else {
            $this->warn("No deposits found in deposit table.");
        }

        // Check if there are any messages from other users containing this username (Internal receive)
        $this->info("\n[+] CHECKING IF ANYONE SENT MONEY TO THIS USER...");
        $received = DB::table('message')
            ->where('role', 'transfer_sent')
            ->where('message', 'like', "%to {$username}%")
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        if (count($received) > 0) {
            $this->info("Found " . count($received) . " transfers received.");
            $recvHeaders = ['Sender', 'Date', 'Amount', 'Message'];
            $recvData = [];
            foreach ($received as $r) {
                $recvData[] = [$r->username, $r->date ?? 'N/A', $r->amount ?? 0, substr($r->message ?? '', 0, 40)];
            }
            $this->table($recvHeaders, $recvData);
        }

        // Print full user details
        $this->info("\n[+] CURRENT USER DETAILS");
        $this->line("Balance: " . $user->bal);
        $this->line("Ref Balance: " . $user->refbal);
        $this->line("Status: " . $user->status);
        $this->line("Device ID: " . $user->device_id);

        return 0;
    }
}
