<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\Contracts\BankingProviderInterface;
use App\Services\KobopointService;
use Exception;
use Illuminate\Support\Facades\Log;

class KobopointProvider implements BankingProviderInterface
{
    protected KobopointService $kobopointService;

    public function __construct()
    {
        $this->kobopointService = new KobopointService();
    }

    /**
     * Get the unique slug for this provider.
     */
    public function getProviderSlug(): string
    {
        return 'kobopoint';
    }

    /**
     * Return a standardized list of banks.
     * Expected format: array of ['name' => string, 'code' => string, 'active' => bool]
     */
    public function getBanks(): array
    {
        $response = $this->kobopointService->getBanks();

        if (!$response['status'] || empty($response['data'])) {
            return [];
        }

        return collect($response['data'])->map(function ($bank) {
            return [
                'name'   => $bank['bankName'] ?? $bank['name'] ?? '',
                'code'   => $bank['bankCode'] ?? $bank['code'] ?? '',
                'active' => true, // Assuming all returned banks are active
            ];
        })->toArray();
    }

    /**
     * Verify an account number.
     * Expected return: ['account_name' => string, 'account_number' => string]
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $response = $this->kobopointService->resolveAccount($bankCode, $accountNumber);

        if (!$response['status']) {
            throw new Exception($response['message'] ?? 'Could not verify account.');
        }

        $data = $response['data'] ?? [];

        return [
            'account_name'   => $data['AccountName'] ?? $data['account_name'] ?? 'Unknown',
            'account_number' => $data['accountNumber'] ?? $data['account_number'] ?? $accountNumber,
        ];
    }

    /**
     * Initiate a transfer.
     * Parameter $details will contain keys: bank_code, account_number, amount, narration, reference, callback_url
     * Expected return: ['status' => true|false, 'message' => string, 'reference' => string|null]
     */
    public function transfer(array $details): array
    {
        try {
            $businessId = config('kobopoint.business_id', env('KOBOPOINT_BUSINESS_ID'));

            if (empty($businessId)) {
                throw new Exception('Kobopoint business ID is missing from configuration.');
            }

            $payload = [
                'businessId'    => $businessId,
                'amount'        => (float) $details['amount'],
                'bank'          => $details['bank_code'],
                'accountNumber' => $details['account_number'],
                'narration'     => $details['narration'] ?? 'Transfer',
            ];

            Log::info("Kobopoint Transfer Initiate Payload:", $payload);

            $response = $this->kobopointService->initiateTransfer($payload);

            if ($response['status'] === true) {
                return [
                    'status'    => true,
                    'message'   => $response['message'] ?? 'Transfer initiated successfully',
                    'reference' => $response['data']['reference'] ?? $response['reference'] ?? null,
                ];
            }

            return [
                'status'  => false,
                'message' => $response['message'] ?? 'Transfer failed',
            ];

        } catch (Exception $e) {
            Log::error('Kobopoint Transfer Exception: ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
