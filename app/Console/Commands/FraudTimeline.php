<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FraudTimeline extends Command
{
    protected $signature = 'fraud:timeline {usernames : Comma-separated usernames}';
    protected $description = 'Full dated timeline of all fraud transactions across linked accounts';

    public function handle()
    {
        $usernames = array_map('trim', explode(',', $this->argument('usernames')));

        // Get all target users
        $targetUsers = DB::table('user')->whereIn('username', $usernames)->get();

        if ($targetUsers->isEmpty()) {
            $this->error("No users found!");
            return;
        }

        // Find linked accounts (same device, phone, email, bvn, nin)
        $deviceIds = $targetUsers->pluck('device_id')->filter()->unique()->toArray();
        $phones = $targetUsers->pluck('phone')->filter()->unique()->toArray();
        $emails = $targetUsers->pluck('email')->filter()->unique()->toArray();
        $bvns = $targetUsers->pluck('bvn')->filter()->unique()->toArray();
        $nins = $targetUsers->pluck('nin')->filter()->unique()->toArray();

        $allUsers = DB::table('user')->where(function ($q) use ($deviceIds, $phones, $emails, $bvns, $nins) {
            if (!empty($deviceIds)) $q->orWhereIn('device_id', $deviceIds);
            if (!empty($phones)) $q->orWhereIn('phone', $phones);
            if (!empty($emails)) $q->orWhereIn('email', $emails);
            if (!empty($bvns)) $q->orWhereIn('bvn', $bvns);
            if (!empty($nins)) $q->orWhereIn('nin', $nins);
        })->get();

        $allUsernames = $allUsers->pluck('username')->toArray();
        $allUserIds = $allUsers->pluck('id')->toArray();

        $this->info("=================================================================");
        $this->info("  📅 FRAUD TIMELINE - ALL TRANSACTIONS BY DATE");
        $this->info("  Generated: " . now()->format('Y-m-d H:i:s'));
        $this->info("  Accounts: " . implode(', ', $allUsernames));
        $this->info("=================================================================\n");

        // Collect ALL events into one timeline
        $timeline = collect();

        // 1. All message table entries (deposits, purchases, internal transfers)
        $messages = DB::table('message')
            ->whereIn('username', $allUsernames)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($messages as $m) {
            $type = '❓ Other';
            $direction = '';
            $role = $m->role ?? '';

            if ($role === 'credit') {
                $type = '💰 DEPOSIT';
                $direction = 'IN';
            } elseif ($role === 'transfer_sent') {
                $type = '📤 INT TRANSFER SENT';
                $direction = 'OUT';
            } elseif ($role === 'transfer_received') {
                $type = '📥 INT TRANSFER RECV';
                $direction = 'IN';
            } elseif ($role === 'transfer') {
                $type = '🏧 BANK WITHDRAWAL';
                $direction = 'OUT';
            } elseif (str_contains($m->message ?? '', 'Airtime')) {
                $type = '📱 AIRTIME';
                $direction = 'OUT';
            } elseif (str_contains($m->message ?? '', 'Data')) {
                $type = '📶 DATA';
                $direction = 'OUT';
            } elseif (str_contains($m->message ?? '', 'Electricity') || str_contains($m->message ?? '', 'Cable') || str_contains($m->message ?? '', 'Exam')) {
                $type = '📋 BILL';
                $direction = 'OUT';
            } else {
                $type = '📝 ' . strtoupper(substr($role, 0, 15));
                $direction = '-';
            }

            $status = '⏳';
            if (($m->plan_status ?? 0) == 1) $status = '✅';
            elseif (($m->plan_status ?? 0) == 2) $status = '❌';

            $timeline->push([
                'date' => $m->habukhan_date ?? $m->date ?? '?',
                'user' => $m->username,
                'type' => $type,
                'dir' => $direction,
                'amount' => $m->amount ?? 0,
                'status' => $status,
                'balance_after' => $m->newbal ?? '-',
                'detail' => substr($m->message ?? '', 0, 55),
                'ref' => $m->transid ?? '-',
                'sort_id' => $m->id,
            ]);
        }

        // 2. All external bank transfers (with destination details)
        $transfers = DB::table('transfers')
            ->whereIn('user_id', $allUserIds)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($transfers as $t) {
            $who = $allUsers->firstWhere('id', $t->user_id);
            $statusIcon = '⏳';
            if (strtoupper($t->status ?? '') === 'SUCCESS') $statusIcon = '✅';
            elseif (strtoupper($t->status ?? '') === 'FAILED') $statusIcon = '❌';

            $dest = ($t->account_name ?? '?') . ' (' . ($t->account_number ?? '?') . ') @ ' . ($t->bank_name ?? $t->bank_code ?? '?');

            $timeline->push([
                'date' => $t->created_at ?? '?',
                'user' => $who->username ?? '?',
                'type' => '🏧 BANK CASHOUT',
                'dir' => 'OUT→BANK',
                'amount' => $t->amount ?? 0,
                'status' => $statusIcon . ' ' . ($t->status ?? '?'),
                'balance_after' => $t->newbal ?? '-',
                'detail' => substr($dest, 0, 55),
                'ref' => $t->reference ?? '-',
                'sort_id' => 900000 + $t->id, // Sort after messages
            ]);
        }

        // Sort by date
        $sorted = $timeline->sortBy('date')->values();

        if ($sorted->isEmpty()) {
            $this->info("No transactions found.");
            return;
        }

        // Print the timeline
        $this->info("Total events: " . $sorted->count() . "\n");

        $rows = [];
        $runningTotals = [];
        foreach ($sorted as $event) {
            $rows[] = [
                $event['date'],
                $event['user'],
                $event['type'],
                $event['dir'],
                '₦' . number_format($event['amount'], 2),
                $event['status'],
                is_numeric($event['balance_after']) ? '₦' . number_format($event['balance_after'], 2) : $event['balance_after'],
                $event['detail'],
            ];

            // Track running totals
            $u = $event['user'];
            if (!isset($runningTotals[$u])) {
                $runningTotals[$u] = ['in' => 0, 'out' => 0];
            }
            if ($event['dir'] === 'IN') {
                $runningTotals[$u]['in'] += $event['amount'];
            } elseif (in_array($event['dir'], ['OUT', 'OUT→BANK'])) {
                $runningTotals[$u]['out'] += $event['amount'];
            }
        }

        $this->table(
            ['Date', 'User', 'Type', 'Dir', 'Amount', 'Status', 'Bal After', 'Details'],
            $rows
        );

        // Summary
        $this->info("\n=================================================================");
        $this->info("  📊 SUMMARY PER ACCOUNT");
        $this->info("=================================================================\n");

        foreach ($runningTotals as $user => $totals) {
            $userObj = $allUsers->firstWhere('username', $user);
            $this->info("  {$user}:");
            $this->info("    Total IN:          ₦" . number_format($totals['in'], 2));
            $this->info("    Total OUT:         ₦" . number_format($totals['out'], 2));
            $this->info("    Current Balance:   ₦" . number_format($userObj->bal ?? 0, 2));
            $this->info("    Unaccounted:       ₦" . number_format($totals['in'] - $totals['out'] - ($userObj->bal ?? 0), 2));
            $this->line("");
        }

        // Cashout destinations summary
        $this->info("=================================================================");
        $this->info("  🏧 CASHOUT DESTINATIONS (Where money was sent)");
        $this->info("=================================================================\n");

        $destinations = [];
        foreach ($transfers as $t) {
            $who = $allUsers->firstWhere('id', $t->user_id);
            $key = ($t->account_number ?? '') . '|' . ($t->account_name ?? '') . '|' . ($t->bank_name ?? $t->bank_code ?? '');
            if (!isset($destinations[$key])) {
                $destinations[$key] = ['total' => 0, 'count' => 0, 'statuses' => [], 'users' => [], 'dates' => []];
            }
            $destinations[$key]['total'] += ($t->amount ?? 0);
            $destinations[$key]['count']++;
            $destinations[$key]['statuses'][] = $t->status ?? '?';
            $destinations[$key]['users'][] = $who->username ?? '?';
            $destinations[$key]['dates'][] = $t->created_at ?? '?';
        }

        $destRows = [];
        foreach ($destinations as $dest => $info) {
            list($acctNo, $acctName, $bank) = explode('|', $dest);
            $destRows[] = [
                $acctNo,
                $acctName,
                $bank,
                $info['count'],
                '₦' . number_format($info['total'], 2),
                implode(', ', array_unique($info['statuses'])),
                implode(', ', array_unique($info['users'])),
                min($info['dates']) . ' → ' . max($info['dates']),
            ];
        }

        if (!empty($destRows)) {
            $this->table(
                ['Acct No', 'Acct Name', 'Bank', 'Txns', 'Total', 'Status', 'From Users', 'Date Range'],
                $destRows
            );
        } else {
            $this->info("No cashout destinations found.");
        }

        $this->info("\n✅ TIMELINE COMPLETE\n");
    }
}
