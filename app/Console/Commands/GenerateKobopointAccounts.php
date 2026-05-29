<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PointWaveVirtualAccount;
use App\Services\KobopointService;
use Illuminate\Support\Facades\Log;

class GenerateKobopointAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kobopoint:generate-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new PalmPay virtual accounts from Kobopoint for all users without deleting old PointWave accounts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(KobopointService $kobopointService)
    {
        $this->info('Starting Kobopoint Virtual Account Generation...');

        // Get all users who have an email (required for virtual accounts)
        $users = User::whereNotNull('email')->get();
        $totalUsers = $users->count();
        $createdCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        $bar = $this->output->createProgressBar($totalUsers);

        foreach ($users as $user) {
            try {
                // Check if user already has a Kobopoint account (customer_id starts with KBP_)
                $hasKobopoint = PointWaveVirtualAccount::where('user_id', $user->id)
                    ->where('customer_id', 'LIKE', 'KBP_%')
                    ->exists();

                if ($hasKobopoint) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Parse name into first and last
                $nameParts = explode(' ', $user->name ?? 'User Account', 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? $nameParts[0];
                
                // Format phone number
                $phoneNumber = $user->phone ?? '08000000000';
                if (str_starts_with($phoneNumber, '+234')) {
                    $phoneNumber = '0' . substr($phoneNumber, 4);
                } elseif (str_starts_with($phoneNumber, '234')) {
                    $phoneNumber = '0' . substr($phoneNumber, 3);
                }

                // If not, create a new Kobopoint virtual account
                $payload = [
                    'email' => $user->email,
                    'phone_number' => $phoneNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'bvn' => '',
                    'nin' => '',
                    'external_reference' => 'USER_' . $user->id
                ];

                // Bank code 100033 is PalmPay
                $accountResult = $kobopointService->createVirtualAccount($payload, $user->name, 'static', ['100033']);

                if (isset($accountResult['status']) && $accountResult['status']) {
                    $responseData = $accountResult['data'];
                    $bankAccount = $responseData['bankAccounts'][0] ?? [];
                    $customerId = $responseData['customer']['customer_id'] ?? ('KBP_' . $user->id);

                    PointWaveVirtualAccount::create([
                        'user_id' => $user->id,
                        'customer_id' => $customerId,
                        'account_number' => $bankAccount['accountNumber'] ?? null,
                        'account_name' => $bankAccount['accountName'] ?? $user->name,
                        'bank_name' => $bankAccount['bankName'] ?? 'PalmPay',
                        'bank_code' => $bankAccount['bankCode'] ?? '100033',
                        'status' => 'active',
                        'external_reference' => $responseData['reference'] ?? null,
                    ]);

                    $createdCount++;
                } else {
                    Log::error("Failed to generate Kobopoint account for User {$user->id}", ['response' => $accountResult]);
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Exception generating Kobopoint account for User {$user->id}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
            
            // Sleep slightly to avoid rate limiting from Kobopoint API
            usleep(200000); // 0.2 seconds
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("Generation Complete!");
        $this->info("Total Users Processed: $totalUsers");
        $this->info("Newly Created Accounts: $createdCount");
        $this->info("Skipped (Already Had Kobopoint): $skippedCount");
        $this->info("Failed: $failedCount");

        return 0;
    }
}
