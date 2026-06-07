<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FraudScanSystem extends Command
{
    protected $signature = 'fraud:scan-system';
    protected $description = 'Scans the entire database to detect hidden fraud rings sharing the same Device ID, BVN, NIN, Email, or Phone Number.';

    public function handle()
    {
        $this->info("=================================================================");
        $this->info("  🚨 SYSTEM-WIDE FRAUD FINGERPRINT SCAN");
        $this->info("  Generated: " . now()->format('Y-m-d H:i:s'));
        $this->info("=================================================================\n");

        $this->info("Scanning for accounts sharing the same unique identifiers...\n");

        $this->scanField('device_id', 'DEVICE ID');
        $this->scanField('bvn', 'BVN');
        $this->scanField('nin', 'NIN');
        $this->scanField('email', 'EMAIL ADDRESS');
        
        // Scan for similarly named users (e.g. variations of QASA SOLAR)
        $this->scanSimilarNames();

        $this->info("✅ SYSTEM SCAN COMPLETE.");
    }

    private function scanField($field, $label)
    {
        // Get fingerprints that are shared by more than 1 user
        // Exclude empty, null, and common placeholder values like '-', '0', 'N/A'
        $duplicates = DB::table('user')
            ->select($field, DB::raw('count(*) as total'))
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->where($field, '!=', '-')
            ->where($field, '!=', '0')
            ->where($field, '!=', 'N/A')
            ->where($field, '!=', 'null')
            ->groupBy($field)
            ->having('total', '>', 1)
            ->orderBy('total', 'desc')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info("✅ No overlapping $label found among multiple accounts.");
            $this->info("-----------------------------------------------------------------\n");
            return;
        }

        $this->error("⚠️ WARNING: Found " . count($duplicates) . " $label(s) shared by multiple accounts!");
        
        foreach ($duplicates as $dup) {
            $users = DB::table('user')
                ->select('id', 'username', 'name', 'status', 'bal', 'reason')
                ->where($field, $dup->{$field})
                ->get();
                
            $this->line("  Shared $label: " . $dup->{$field} . " (Used by " . $dup->total . " accounts)");
            
            $tableData = [];
            foreach ($users as $u) {
                $statusText = $u->status == 2 ? '🚫 BANNED' : ($u->status == 1 ? '✅ ACTIVE' : '⚠️ OTHER');
                $tableData[] = [
                    'ID' => $u->id,
                    'Username' => $u->username,
                    'Name' => substr($u->name, 0, 25),
                    'Balance' => '₦' . number_format($u->bal ?? 0, 2),
                    'Status' => $statusText
                ];
            }
            $this->table(['ID', 'Username', 'Name', 'Balance', 'Status'], $tableData);
            $this->line("");
        }
        $this->info("-----------------------------------------------------------------\n");
    }

    private function scanSimilarNames()
    {
        $this->info("Searching for suspicious repeated names (e.g., Solar, Energy, Qasa)...");
        $keywords = ['solar', 'energy', 'qasa', 'sunking', 'enterprise'];
        
        foreach ($keywords as $keyword) {
            $users = DB::table('user')
                ->select('id', 'username', 'name', 'status', 'bal')
                ->where('name', 'like', "%$keyword%")
                ->orderBy('name')
                ->get();

            if ($users->count() > 1) {
                $this->warn("  👉 Found " . $users->count() . " accounts containing '$keyword' in their name:");
                
                $tableData = [];
                foreach ($users as $u) {
                    $statusText = $u->status == 2 ? '🚫 BANNED' : ($u->status == 1 ? '✅ ACTIVE' : '⚠️ OTHER');
                    $tableData[] = [
                        'Username' => $u->username,
                        'Name' => substr($u->name, 0, 35),
                        'Status' => $statusText,
                        'Balance' => '₦' . number_format($u->bal ?? 0, 2),
                    ];
                }
                $this->table(['Username', 'Name', 'Status', 'Balance'], $tableData);
            }
        }
        $this->info("-----------------------------------------------------------------\n");
    }
}
