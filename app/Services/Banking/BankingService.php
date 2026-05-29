<?php

namespace App\Services\Banking;

use App\Services\Banking\Providers\PaystackProvider;
use App\Services\Banking\Providers\XixapayProvider;
use App\Services\Banking\Providers\MonnifyProvider;
use App\Services\Banking\Providers\PointWaveProvider;
use App\Services\Banking\Providers\KobopointProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankingService
{
    /**
     * Resolve a provider instance by slug.
     */
    public function resolveProvider(string $slug): BankingProviderInterface
    {
        switch (strtolower($slug)) {
            case 'pointwave':
                return new PointWaveProvider();
            case 'xixapay':
                return new XixapayProvider();
            case 'monnify':
                return new MonnifyProvider();
            case 'paystack':
                return new PaystackProvider();
            case 'kobopoint':
                return new KobopointProvider();
            default:
                return new PointWaveProvider(); // Default to PointWave
        }
    }

    /**
     * Get the currently active primary transfer provider.
     * Uses PointWave as the primary provider for transfers.
     * Falls back to Xixapay if PointWave is unavailable.
     */
    public function getActiveProvider(): BankingProviderInterface
    {
        // Check settings for preferred provider
        $settings = DB::table('settings')->first();
        $preferredProvider = $settings->primary_transfer_provider ?? $settings->transfer_provider ?? 'pointwave';
        
        // Allow fallback to other providers if explicitly set
        return $this->resolveProvider($preferredProvider);
    }

    /**
     * Verify an account number.
     * Uses the active provider (PointWave by default).
     * Automatically converts old bank codes to PointWave codes.
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $provider = $this->getActiveProvider();
        $providerSlug = $provider->getProviderSlug();

        try {
            // Resolve the generic bank code to the provider's specific code
            $resolvedCode = $this->resolveBankCode($bankCode, $providerSlug);
            
            if ($resolvedCode !== $bankCode) {
                Log::info("BankingService: Converted bank code", [
                    'provider' => $providerSlug,
                    'old_code' => $bankCode,
                    'new_code' => $resolvedCode
                ]);
            }
            
            return $provider->verifyAccount($accountNumber, $resolvedCode);

        }
        catch (\Exception $e) {
            Log::error("BankingService: Verification failed ({$providerSlug}): " . $e->getMessage());

            // Temporary Fallback to PointWave for verification since Kobopoint is broken
            if ($providerSlug !== 'pointwave') {
                try {
                    $pwProvider = $this->resolveProvider('pointwave');
                    $pwCode = $this->resolveBankCode($bankCode, 'pointwave');
                    return $pwProvider->verifyAccount($accountNumber, $pwCode);
                } catch (\Exception $fallbackE) {
                    // Don't throw yet, try Paystack next
                }
            }

            // Ultimate Fallback to Paystack if the above failed
            if ($providerSlug !== 'paystack') {
                try {
                    $psProvider = $this->resolveProvider('paystack');
                    $psCode = $this->resolveBankCode($bankCode, 'paystack');
                    return $psProvider->verifyAccount($accountNumber, $psCode);
                } catch (\Exception $psFallbackE) {
                    throw $e; // Throw original error
                }
            }

            throw $e;
        }
    }
    
    /**
     * Convert old bank codes (Paystack/legacy) to PointWave codes
     */
    private function convertToPointWaveCode(string $oldCode): string
    {
        // Try to find the bank by old code and get its PointWave code
        // Only check paystack_code since xixapay_code and monnify_code don't exist in the table
        $bank = DB::table('unified_banks')
            ->where('paystack_code', $oldCode)
            ->whereNotNull('pointwave_code')
            ->where('pointwave_code', '!=', '')
            ->first();
        
        if ($bank && !empty($bank->pointwave_code)) {
            return $bank->pointwave_code;
        }
        
        // If no conversion found, return original code
        return $oldCode;
    }

    /**
     * Initiate a transfer.
     * Uses the active provider (PointWave by default).
     * Automatically converts old bank codes to PointWave codes.
     */
    public function transfer(array $details): array
    {
        $provider = $this->getActiveProvider();
        $providerSlug = $provider->getProviderSlug();

        // Resolve the generic bank code to the provider's specific code
        $resolvedCode = $this->resolveBankCode($details['bank_code'], $providerSlug);
        
        if ($resolvedCode !== $details['bank_code']) {
            Log::info("BankingService: Converted bank code for transfer", [
                'provider' => $providerSlug,
                'old_code' => $details['bank_code'],
                'new_code' => $resolvedCode
            ]);
        }
        
        $details['bank_code'] = $resolvedCode;

        try {
            $result = $provider->transfer($details);
            
            // Add bank name to result if not present
            if (!isset($result['bank_name'])) {
                $bank = DB::table('unified_banks')->where('code', $details['bank_code'])->first();
                $result['bank_name'] = $bank ? $bank->name : null;
            }
            
            return $result;
        }
        catch (\Exception $e) {
            Log::error("BankingService: Transfer Error ({$providerSlug}): " . $e->getMessage());
            return [
                'status' => 'fail',
                'message' => 'Transfer failed. ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get list of supported banks from the Unified Database.
     * Returns banks with their codes for the active provider.
     */
    public function getSupportedBanks()
    {
        try {
            $provider = $this->getActiveProvider();
            $providerSlug = $provider->getProviderSlug();
            
            // For PointWave, use the 'code' column (primary bank code)
            // For other providers, use their specific code columns
            if ($providerSlug === 'pointwave') {
                return DB::table('unified_banks')
                    ->where('active', true)
                    ->whereNotNull('code')
                    ->where('code', '!=', '')
                    ->orderBy('name')
                    ->get();
            }
            
            // For other providers (paystack, xixapay, monnify)
            return DB::table('unified_banks')
                ->where('active', true)
                ->whereNotNull("{$providerSlug}_code")
                ->where("{$providerSlug}_code", '!=', '')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::error('getSupportedBanks Error: ' . $e->getMessage());
            return collect([]); // Return empty collection so fallback kicks in
        }
    }

    /**
     * Sync banks from a specific provider to the Unified Database.
     */
    public function syncBanksFromProvider(string $providerSlug)
    {
        $provider = $this->resolveProvider($providerSlug);
        $banks = $provider->getBanks();

        $count = 0;
        foreach ($banks as $bank) {
            $existing = DB::table('unified_banks')->where('code', $bank['code'])->first();

            if (!$existing) {
                DB::table('unified_banks')->insert([
                    'name' => $bank['name'],
                    'code' => $bank['code'],
                    "{$providerSlug}_code" => $bank['code'],
                    'active' => $bank['active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $count++;
            }
            else {
                DB::table('unified_banks')->where('id', $existing->id)->update([
                    "{$providerSlug}_code" => $bank['code'],
                    'active' => $bank['active'] ?? true,
                    'updated_at' => now()
                ]);
            }
        }
        return $count;
    }

    /**
     * Helper to resolve generic bank code to provider specific code.
     */
    private function resolveBankCode(string $genericCode, string $providerSlug): string
    {
        $bank = DB::table('unified_banks')->where('code', $genericCode)->first();
        if ($bank && !empty($bank->{ "{$providerSlug}_code"})) {
            return $bank->{ "{$providerSlug}_code"};
        }
        return $genericCode;
    }
}