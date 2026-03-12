<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\BankingProviderInterface;

class MonnifyProvider implements BankingProviderInterface
{
    public function getProviderSlug(): string
    {
        return 'monnify';
    }

    public function getBanks(): array
    {
        // TODO: Implement actual Monnify API call
        // Returning empty array so it doesn't break the sync logic (it will just add nothing)
        return [];
    }

    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        // Mock Successful Verification for now
        // In future: Implement https://api.monnify.com/api/v1/disbursements/account/validate
        return [
            'status' => 'success',
            'data' => [
                'account_name' => 'Monnify Verified User (Mock)',
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]
        ];
    }

    public function transfer(array $details): array
    {
        // Mock Successful Transfer
        return [
            'status' => 'pending',
            'message' => 'Monnify transfer initiated (Mock)',
            'reference' => $details['reference'],
            'provider_reference' => 'MNFY_' . time()
        ];
    }

    public function getBalance(): float
    {
        return 999999.99; // Mock Balance
    }

    public function queryTransfer(string $reference): array
    {
        return [
            'status' => 'success',
            'message' => 'Status retrieved (Mock)',
            'data' => ['status' => 'SUCCESSFUL']
        ];
    }
}
