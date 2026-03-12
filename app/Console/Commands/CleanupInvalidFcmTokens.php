<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class CleanupInvalidFcmTokens extends Command
{
    protected $signature = 'fcm:cleanup-invalid-tokens {--dry-run : Show what would be cleaned without actually cleaning}';
    protected $description = 'Test and cleanup invalid FCM tokens from the database';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting FCM token validation...');
        
        // Get all users with FCM tokens
        $users = DB::table('user')
            ->whereNotNull('app_token')
            ->where('app_token', '!=', '')
            ->select('id', 'username', 'app_token')
            ->get();
        
        if ($users->isEmpty()) {
            $this->info('No FCM tokens found in database.');
            return 0;
        }
        
        $this->info("Found {$users->count()} users with FCM tokens.");
        $this->info('Testing tokens in batches...');
        
        $firebase = new FirebaseService();
        $invalidTokens = [];
        $validCount = 0;
        
        // Test tokens in small batches
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        foreach ($users as $user) {
            // Try to send a test data-only message (won't show notification)
            $testResult = $firebase->sendNotification(
                $user->app_token,
                'Test',
                'Token validation',
                ['test' => 'true'],
                null,
                true // data-only, won't show notification
            );
            
            if (!$testResult) {
                $invalidTokens[] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'token' => substr($user->app_token, 0, 20) . '...'
                ];
            } else {
                $validCount++;
            }
            
            $bar->advance();
            usleep(100000); // 100ms delay to avoid rate limiting
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Display results
        $this->info("Validation complete!");
        $this->info("Valid tokens: {$validCount}");
        $this->info("Invalid tokens: " . count($invalidTokens));
        
        if (!empty($invalidTokens)) {
            $this->newLine();
            $this->warn('Invalid tokens found:');
            
            $headers = ['User ID', 'Username', 'Token (truncated)'];
            $this->table($headers, $invalidTokens);
            
            if ($isDryRun) {
                $this->info('DRY RUN: No tokens were removed. Run without --dry-run to cleanup.');
            } else {
                if ($this->confirm('Do you want to remove these invalid tokens from the database?', true)) {
                    $userIds = array_column($invalidTokens, 'user_id');
                    $deleted = DB::table('user')
                        ->whereIn('id', $userIds)
                        ->update(['app_token' => null]);
                    
                    $this->info("Cleaned up {$deleted} invalid FCM tokens.");
                    Log::info("Manual FCM token cleanup completed", [
                        'cleaned' => $deleted,
                        'valid' => $validCount
                    ]);
                } else {
                    $this->info('Cleanup cancelled.');
                }
            }
        } else {
            $this->info('All tokens are valid!');
        }
        
        return 0;
    }
}
