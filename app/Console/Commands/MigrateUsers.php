<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:users {file=users_export_2026.json} {--force : Update existing users}';

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $description = 'Import users from legacy JSON export';

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
        $filePath = base_path($this->argument('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting migration from {$filePath}...");

        $jsonContent = file_get_contents($filePath);
        $users = json_decode($jsonContent, true);

        if ($users === null) {
            $this->error("Failed to decode JSON file.");
            return 1;
        }

        $total = count($users);
        $this->info("Found {$total} users to process.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;
        $updateCount = 0;

        DB::beginTransaction();

        try {
            foreach ($users as $userData) {
                $username = substr($userData['username'] ?? '', 0, 25);
                $email = $userData['email'] ?? '';
                $phone = $userData['phone'] ?? '';
                $name = substr($userData['name'] ?? '', 0, 255);

                // Sanitize for latin1 compatibility
                $username = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $username);
                $name = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $name);
                $email = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $email);

                if (empty($username)) {
                    $failCount++;
                    $bar->advance();
                    continue;
                }

                $bankInfo = [
                    'palmpay' => null,
                    'wema' => null,
                    'monify_ref' => null,
                    'sterlen' => null,
                    'fed' => null,
                    'pro' => null,
                    'sb' => null,
                    'safe' => null,
                ];

                // Parse Bank Data 1 (Palmpay & Monify Ref)
                if (!empty($userData['bank_data_1'])) {
                    $bankData1 = json_decode($userData['bank_data_1'], true);
                    if (is_array($bankData1)) {
                        foreach ($bankData1 as $bank) {
                            $bankName = $bank['bankName'] ?? '';
                            $accNo = $bank['accountNumber'] ?? null;
                            if (stripos($bankName, 'Palmpay') !== false) {
                                $bankInfo['palmpay'] = $accNo;
                                if (isset($bank['Reserved_Account_Id'])) {
                                    $bankInfo['monify_ref'] = $bank['Reserved_Account_Id'];
                                }
                            }
                        }
                    }
                }

                // Parse Bank Data 2 (Moniepoint, Wema, Sterling, Fidelity, GTBank, etc.)
                if (!empty($userData['bank_data_2'])) {
                    $bankData2 = json_decode($userData['bank_data_2'], true);
                    if (is_array($bankData2)) {
                        foreach ($bankData2 as $bank) {
                            $bankName = $bank['bankName'] ?? '';
                            $bankCode = $bank['bankCode'] ?? '';
                            $accNo = $bank['accountNumber'] ?? null;

                            // Moniepoint code 50515 or name Moniepoint
                            // Mapped to 'wema' column for dashboard display priority
                            if ($bankCode == '50515' || stripos($bankName, 'Moniepoint') !== false) {
                                $bankInfo['wema'] = $accNo;
                                if (empty($bankInfo['monify_ref']) && isset($bank['Reserved_Account_Id'])) {
                                    $bankInfo['monify_ref'] = $bank['Reserved_Account_Id'];
                                }
                            }
                            // GTBank: Code 058 or name GTBank
                            elseif ($bankCode == '058' || stripos($bankName, 'GTBank') !== false) {
                                $bankInfo['sb'] = $accNo;
                            }
                            // Wema: Code 035 or name Wema (Fallback for wema column)
                            elseif ($bankCode == '035' || stripos($bankName, 'Wema') !== false) {
                                if (empty($bankInfo['wema'])) {
                                    $bankInfo['wema'] = $accNo;
                                }
                            }
                            // Sterling: Code 232
                            elseif ($bankCode == '232' || stripos($bankName, 'Sterling') !== false) {
                                $bankInfo['sterlen'] = $accNo;
                            }
                            // Fidelity: Code 070
                            elseif ($bankCode == '070' || stripos($bankName, 'Fidelity') !== false) {
                                $bankInfo['fed'] = $accNo;
                            }
                            // 9PSB: Code 120001
                            elseif ($bankCode == '120001' || stripos($bankName, '9PSB') !== false) {
                                $bankInfo['safe'] = $accNo;
                            }
                            // Providus: Code 101 or 076
                            elseif ($bankCode == '101' || $bankCode == '076' || stripos($bankName, 'Providus') !== false) {
                                $bankInfo['pro'] = $accNo;
                            }
                        }
                    }
                }

                $existingUser = DB::table('user')
                    ->whereRaw("username = CONVERT(? USING latin1)", [$username])
                    ->first();

                if ($existingUser) {
                    // Update: Ensure type is SMART and bank accounts are filled
                    $updateData = ['type' => 'SMART'];
                    foreach ($bankInfo as $key => $val) {
                        if (!empty($val) && empty($existingUser->$key)) {
                            $updateData[$key] = $val;
                        }
                    }

                    if (!empty($updateData)) {
                        DB::table('user')->where('id', $existingUser->id)->update($updateData);
                        $updateCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    // Insert new user
                    $insertData = array_merge([
                        'name' => $name,
                        'username' => $username,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $userData['password'] ?? null,
                        'pin' => $userData['pin'] ?? null,
                        'bal' => (float) ($userData['balance_raw'] ?? 0),
                        'kyc' => $userData['kyc'] ?? 0,
                        'type' => 'SMART',
                        'status' => $userData['status'] ?? 1,
                        'ref' => $userData['refBy'] ?? null,
                        'refbal' => (float) ($userData['bonus'] ?? 0),
                        'address' => $userData['address'] ?? null,
                        'date' => $userData['regDate'] ?? now(),
                        'apikey' => Str::random(30),
                        'app_token' => Str::random(60),
                        'autofund' => 0,
                        'user_limit' => 0,
                        'vdf' => null,
                        'is_bvn_fail' => 0,
                        'habukhan_key' => null,
                        'app_key' => null,
                        'bvn' => null,
                        'sb' => null,
                        'safe' => null,
                        'pro' => null,
                        'rolex' => null,
                        'about' => null,
                        'otp' => null,
                        'reason' => null,
                        'webhook' => null,
                        'paystack_account' => null,
                        'paystack_bank' => null,
                        'profile_image' => null,
                    ], $bankInfo);

                    try {
                        DB::table('user')->insert($insertData);
                        $successCount++;
                    } catch (\Exception $e) {
                        $failCount++;
                        Log::error("Failed to migrate user {$username}: " . $e->getMessage());
                    }
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info("Process completed.");
            $this->info("New Users: {$successCount}");
            $this->info("Updated Users: {$updateCount}");
            $this->info("Skipped: {$skippedCount}");
            $this->info("Failed: {$failCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Process failed: " . $e->getMessage());
            Log::error("User Migration Global Failure: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
