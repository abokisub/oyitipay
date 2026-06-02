<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InvestigateFraud extends Command
{
    protected $signature = 'fraud:investigate {usernames : Comma-separated usernames e.g. akinnew1,Sunking101,ENERGY700}';
    protected $description = 'Full fraud investigation: duplicate accounts, linked identities, money flow tracking';

    public function handle()
    {
        $usernames = explode(',', $this->argument('usernames'));
        $usernames = array_map('trim', $usernames);

        $this->info("=================================================================");
        $this->info("  🔍 FRAUD INVESTIGATION REPORT");
        $this->info("  Generated: " . now()->format('Y-m-d H:i:s'));
        $this->info("  Targets: " . implode(', ', $usernames));
        $this->info("=================================================================\n");

        // Step 1: Get all target user profiles
        $targetUsers = DB::table('user')->whereIn('username', $usernames)->get();

        if ($targetUsers->isEmpty()) {
            $this->error("No users found with those usernames!");
            return;
        }

        // Collect all fingerprints
        $deviceIds = [];
        $phones = [];
        $emails = [];
        $bvns = [];
        $nins = [];
        $names = [];
        $ips = [];

        foreach ($targetUsers as $user) {
            $this->printUserProfile($user);

            if (!empty($user->device_id)) $deviceIds[] = $user->device_id;
            if (!empty($user->phone)) $phones[] = $user->phone;
            if (!empty($user->email)) $emails[] = $user->email;
            if (!empty($user->name)) $names[] = $user->name;
            // Try common BVN/NIN column names
            foreach (['bvn', 'bvn_number', 'nin', 'nin_number', 'nin_id'] as $col) {
                try {
                    if (!empty($user->$col)) {
                        if (str_contains($col, 'bvn')) $bvns[] = $user->$col;
                        if (str_contains($col, 'nin')) $nins[] = $user->$col;
                    }
                } catch (\Exception $e) {}
            }
            try {
                if (!empty($user->ip) || !empty($user->last_login_ip) || !empty($user->register_ip)) {
                    if (!empty($user->ip)) $ips[] = $user->ip;
                    if (!empty($user->last_login_ip)) $ips[] = $user->last_login_ip;
                    if (!empty($user->register_ip)) $ips[] = $user->register_ip;
                }
            } catch (\Exception $e) {}
        }

        $deviceIds = array_unique(array_filter($deviceIds));
        $phones = array_unique(array_filter($phones));
        $emails = array_unique(array_filter($emails));
        $bvns = array_unique(array_filter($bvns));
        $nins = array_unique(array_filter($nins));
        $ips = array_unique(array_filter($ips));

        // ============================================================
        // Step 2: Find ALL linked/duplicate accounts
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  🔗 LINKED / DUPLICATE ACCOUNTS (Same Device, Phone, Email, BVN, NIN)");
        $this->info("=================================================================\n");

        $linkedQuery = DB::table('user')->where(function ($q) use ($deviceIds, $phones, $emails, $bvns, $nins, $usernames) {
            if (!empty($deviceIds)) $q->orWhereIn('device_id', $deviceIds);
            if (!empty($phones)) $q->orWhereIn('phone', $phones);
            if (!empty($emails)) $q->orWhereIn('email', $emails);
            // Try BVN/NIN columns
            foreach (['bvn', 'bvn_number'] as $col) {
                if (!empty($bvns)) {
                    try { $q->orWhereIn($col, $bvns); } catch (\Exception $e) {}
                }
            }
            foreach (['nin', 'nin_number', 'nin_id'] as $col) {
                if (!empty($nins)) {
                    try { $q->orWhereIn($col, $nins); } catch (\Exception $e) {}
                }
            }
        });

        $linkedUsers = $linkedQuery->get();

        // Add these linked users to our target pool
        $allInvestigatedUsernames = $usernames;
        
        if ($linkedUsers->count() > count($usernames)) {
            $this->warn("⚠️  Found " . $linkedUsers->count() . " accounts linked to the suspects!\n");

            $headers = ['Username', 'Name', 'Phone', 'Email', 'Device ID', 'Balance', 'Status', 'Match Reason'];
            $rows = [];

            foreach ($linkedUsers as $lu) {
                $reasons = [];
                if (!empty($lu->device_id) && in_array($lu->device_id, $deviceIds)) $reasons[] = 'DEVICE';
                if (!empty($lu->phone) && in_array($lu->phone, $phones)) $reasons[] = 'PHONE';
                if (!empty($lu->email) && in_array($lu->email, $emails)) $reasons[] = 'EMAIL';
                if (in_array($lu->username, $usernames)) $reasons[] = 'TARGET';

                $status = $lu->status == 1 ? '✅ Active' : ($lu->status == 2 ? '🚫 Banned' : '❓ ' . $lu->status);

                $rows[] = [
                    $lu->username,
                    $lu->name ?? '-',
                    $lu->phone ?? '-',
                    $lu->email ?? '-',
                    substr($lu->device_id ?? '-', 0, 15),
                    '₦' . number_format($lu->bal ?? 0, 2),
                    $status,
                    implode(', ', $reasons)
                ];

                if (!in_array($lu->username, $allInvestigatedUsernames)) {
                    $allInvestigatedUsernames[] = $lu->username;
                }
            }

            $this->table($headers, $rows);
        } else {
            $this->info("No additional linked accounts found beyond targets.");
        }

        // ============================================================
        // Step 3: Virtual Accounts linked to these users  
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  🏦 VIRTUAL ACCOUNTS (Deposit Accounts)");
        $this->info("=================================================================\n");

        foreach ($linkedUsers as $lu) {
            $accounts = array_filter([
                'PalmPay/PointWave' => $lu->palmpay ?? ($lu->pointwave_account_number ?? null),
                'Monnify' => $lu->monnify_account ?? null,
            ]);
            try {
                if (!empty($lu->kobopoint_customer_id)) {
                    $accounts['Kobopoint ID'] = $lu->kobopoint_customer_id;
                }
            } catch (\Exception $e) {}

            if (!empty($accounts)) {
                $this->info("  {$lu->username}: " . implode(' | ', array_map(fn($k, $v) => "$k: $v", array_keys($accounts), $accounts)));
            }
        }

        // ============================================================
        // Step 4: DEPOSITS - All money coming in
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  💰 ALL DEPOSITS (Incoming Funds)");
        $this->info("=================================================================\n");

        foreach ($allInvestigatedUsernames as $uname) {
            $deposits = DB::table('message')
                ->where('username', $uname)
                ->where('role', 'credit')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();

            if ($deposits->isNotEmpty()) {
                $this->info("--- Deposits for: {$uname} ---");
                $depRows = [];
                $totalDeposited = 0;
                foreach ($deposits as $d) {
                    $depRows[] = [
                        $d->habukhan_date ?? '-',
                        '₦' . number_format($d->amount ?? 0, 2),
                        substr($d->message ?? '', 0, 60),
                        $d->transid ?? '-',
                    ];
                    $totalDeposited += ($d->amount ?? 0);
                }
                $this->table(['Date', 'Amount', 'Description', 'Ref'], $depRows);
                $this->info("  Total Deposited: ₦" . number_format($totalDeposited, 2));
                $this->line("");
            }
        }

        // ============================================================
        // Step 5: INTERNAL TRANSFERS SENT
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  📤 INTERNAL TRANSFERS - SENT (Money moving between accounts)");
        $this->info("=================================================================\n");

        foreach ($allInvestigatedUsernames as $uname) {
            $sent = DB::table('message')
                ->where('username', $uname)
                ->where('role', 'transfer_sent')
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get();

            if ($sent->isNotEmpty()) {
                $this->warn("--- Sent by: {$uname} ---");
                $sentRows = [];
                $totalSent = 0;
                foreach ($sent as $s) {
                    $sentRows[] = [
                        $s->habukhan_date ?? '-',
                        '₦' . number_format($s->amount ?? 0, 2),
                        substr($s->message ?? '', 0, 70),
                        $s->transid ?? '-',
                    ];
                    $totalSent += ($s->amount ?? 0);
                }
                $this->table(['Date', 'Amount', 'To (from message)', 'Ref'], $sentRows);
                $this->warn("  Total Sent: ₦" . number_format($totalSent, 2));
                $this->line("");
            }
        }

        // ============================================================
        // Step 6: INTERNAL TRANSFERS RECEIVED  
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  📥 INTERNAL TRANSFERS - RECEIVED");
        $this->info("=================================================================\n");

        foreach ($allInvestigatedUsernames as $uname) {
            $received = DB::table('message')
                ->where('username', $uname)
                ->where('role', 'transfer_received')
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get();

            if ($received->isNotEmpty()) {
                $this->info("--- Received by: {$uname} ---");
                $recRows = [];
                $totalReceived = 0;
                foreach ($received as $r) {
                    $recRows[] = [
                        $r->habukhan_date ?? '-',
                        '₦' . number_format($r->amount ?? 0, 2),
                        substr($r->message ?? '', 0, 70),
                        $r->transid ?? '-',
                    ];
                    $totalReceived += ($r->amount ?? 0);
                }
                $this->table(['Date', 'Amount', 'From (from message)', 'Ref'], $recRows);
                $this->info("  Total Received: ₦" . number_format($totalReceived, 2));
                $this->line("");
            }
        }

        // ============================================================
        // Step 7: EXTERNAL BANK TRANSFERS (WITHDRAWALS)
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  🏧 EXTERNAL BANK TRANSFERS (Withdrawals / Cashout)");
        $this->info("=================================================================\n");

        $userIds = $linkedUsers->pluck('id')->toArray();

        $transfers = DB::table('transfers')
            ->whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($transfers->isNotEmpty()) {
            $extRows = [];
            $totalWithdrawn = 0;
            $withdrawalDestinations = [];

            foreach ($transfers as $t) {
                $who = $linkedUsers->firstWhere('id', $t->user_id);
                $extRows[] = [
                    $who->username ?? '?',
                    $t->created_at ?? '-',
                    '₦' . number_format($t->amount ?? 0, 2),
                    $t->bank_name ?? ($t->bank_code ?? '-'),
                    $t->account_number ?? '-',
                    $t->account_name ?? '-',
                    $t->status ?? '-',
                ];
                if (strtoupper($t->status ?? '') === 'SUCCESS') {
                    $totalWithdrawn += ($t->amount ?? 0);
                    $key = ($t->account_number ?? '') . '|' . ($t->account_name ?? '') . '|' . ($t->bank_name ?? $t->bank_code ?? '');
                    if (!isset($withdrawalDestinations[$key])) {
                        $withdrawalDestinations[$key] = 0;
                    }
                    $withdrawalDestinations[$key] += ($t->amount ?? 0);
                }
            }

            $this->table(['User', 'Date', 'Amount', 'Bank', 'Acct No', 'Acct Name', 'Status'], $extRows);
            $this->warn("\n  💸 Total Successfully Withdrawn: ₦" . number_format($totalWithdrawn, 2));

            if (!empty($withdrawalDestinations)) {
                $this->info("\n--- Withdrawal Destination Summary ---");
                $destRows = [];
                foreach ($withdrawalDestinations as $dest => $total) {
                    list($acctNo, $acctName, $bank) = explode('|', $dest);
                    $destRows[] = [
                        $acctNo,
                        $acctName,
                        $bank,
                        '₦' . number_format($total, 2),
                    ];
                }
                $this->table(['Account No', 'Account Name', 'Bank', 'Total Withdrawn'], $destRows);
            }
        } else {
            $this->info("No external bank transfers found.");
        }

        // ============================================================
        // Step 8: SERVICE PURCHASES (Data, Airtime, etc.)
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  📱 SERVICE PURCHASES (Data, Airtime, Bills)");
        $this->info("=================================================================\n");

        foreach ($allInvestigatedUsernames as $uname) {
            $purchases = DB::table('message')
                ->where('username', $uname)
                ->whereNotIn('role', ['credit', 'transfer_sent', 'transfer_received', 'transfer', 'upgrade'])
                ->where('plan_status', 1)
                ->orderBy('id', 'desc')
                ->limit(15)
                ->get();

            if ($purchases->isNotEmpty()) {
                $this->info("--- Purchases by: {$uname} ---");
                $purchRows = [];
                $totalPurchased = 0;
                foreach ($purchases as $p) {
                    $purchRows[] = [
                        $p->habukhan_date ?? '-',
                        '₦' . number_format($p->amount ?? 0, 2),
                        substr($p->message ?? '', 0, 70),
                    ];
                    $totalPurchased += ($p->amount ?? 0);
                }
                $this->table(['Date', 'Amount', 'Description'], $purchRows);
                $this->info("  Total Purchases: ₦" . number_format($totalPurchased, 2));
                $this->line("");
            }
        }

        // ============================================================
        // Step 9: MONEY FLOW SUMMARY
        // ============================================================
        $this->info("\n=================================================================");
        $this->info("  📊 MONEY FLOW SUMMARY PER ACCOUNT");
        $this->info("=================================================================\n");

        foreach ($allInvestigatedUsernames as $uname) {
            $deposited = DB::table('message')->where('username', $uname)->where('role', 'credit')->sum('amount');
            $intSent = DB::table('message')->where('username', $uname)->where('role', 'transfer_sent')->sum('amount');
            $intReceived = DB::table('message')->where('username', $uname)->where('role', 'transfer_received')->sum('amount');
            
            $userId = $linkedUsers->firstWhere('username', $uname)->id ?? null;
            $extWithdrawn = $userId ? DB::table('transfers')->where('user_id', $userId)->where('status', 'SUCCESS')->sum('amount') : 0;
            
            $currentBal = $linkedUsers->firstWhere('username', $uname)->bal ?? 0;

            $this->info("  {$uname}:");
            $this->info("    Deposited:          ₦" . number_format($deposited, 2));
            $this->info("    Internal Received:  ₦" . number_format($intReceived, 2));
            $this->info("    Internal Sent:      ₦" . number_format($intSent, 2));
            $this->info("    Bank Withdrawn:     ₦" . number_format($extWithdrawn, 2));
            $this->info("    Current Balance:    ₦" . number_format($currentBal, 2));
            $this->line("");
        }

        $this->info("=================================================================");
        $this->info("  ✅ INVESTIGATION COMPLETE");
        $this->info("=================================================================\n");
    }

    private function printUserProfile($user)
    {
        $this->info("---------------------------------------------------");
        $this->info("  👤 USER PROFILE: {$user->username}");
        $this->info("---------------------------------------------------");
        $this->info("  ID:        {$user->id}");
        $this->info("  Name:      " . ($user->name ?? '-'));
        $this->info("  Phone:     " . ($user->phone ?? '-'));
        $this->info("  Email:     " . ($user->email ?? '-'));
        $this->info("  Balance:   ₦" . number_format($user->bal ?? 0, 2));
        $this->info("  Status:    " . ($user->status == 1 ? '✅ Active' : ($user->status == 2 ? '🚫 Banned' : $user->status)));
        $this->info("  Device ID: " . ($user->device_id ?? '-'));

        // Try to show BVN/NIN/KYC
        foreach (['bvn', 'bvn_number', 'nin', 'nin_number', 'kyc_status'] as $col) {
            try {
                if (!empty($user->$col)) {
                    $this->info("  " . strtoupper($col) . ":    " . $user->$col);
                }
            } catch (\Exception $e) {}
        }

        // Virtual accounts
        $accounts = [];
        if (!empty($user->palmpay)) $accounts[] = "PalmPay: {$user->palmpay}";
        if (!empty($user->pointwave_account_number)) $accounts[] = "PointWave: {$user->pointwave_account_number}";
        if (!empty($user->monnify_account)) $accounts[] = "Monnify: {$user->monnify_account}";
        if (!empty($accounts)) {
            $this->info("  Accounts:  " . implode(' | ', $accounts));
        }

        $this->line("");
    }
}
