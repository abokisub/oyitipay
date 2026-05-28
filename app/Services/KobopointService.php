<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KobopointService
{
    private string $baseUrl;
    private string $secretKey;
    private string $businessId;
    private string $apiKey;
    private bool $verifySSL;

    public function __construct()
    {
        $this->baseUrl    = rtrim(config('kobopoint.base_url', env('KOBOPOINT_BASE_URL', 'https://app.kobopoint.com/api/v1')), '/');
        $this->secretKey  = config('kobopoint.secret_key', env('KOBOPOINT_SECRET_KEY', ''));
        $this->businessId = config('kobopoint.business_id', env('KOBOPOINT_BUSINESS_ID', ''));
        $this->apiKey     = config('kobopoint.api_key', env('KOBOPOINT_API_KEY', ''));
        $this->verifySSL  = (bool) env('KOBOPOINT_VERIFY_SSL', true);
    }

    // ------------------------------------------------------------------
    // HTTP helpers
    // ------------------------------------------------------------------

    private function http(int $timeout = 30)
    {
        $client = Http::timeout($timeout)->withHeaders($this->headers());
        if (!$this->verifySSL) {
            $client = $client->withoutVerifying();
        }
        return $client;
    }

    private function headers(): array
    {
        return [
            'businessId'    => $this->businessId,
            'api-key'       => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    private function multipartHeaders(): array
    {
        return [
            'businessId'    => $this->businessId,
            'api-key'       => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Normalise the HTTP response into a consistent ['status', 'data'|'message'] array.
     */
    private function parse($response): array
    {
        $body = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'status'      => true,
                'data'        => $body['data'] ?? $body,
                'http_status' => $response->status(),
            ];
        }

        $message = $body['message'] ?? $body['error'] ?? 'Unknown error';
        Log::error('KobopointService error', [
            'status'  => $response->status(),
            'message' => $message,
            'body'    => $body,
        ]);

        return [
            'status'      => false,
            'message'     => $message,
            'http_status' => $response->status(),
            'raw'         => $body,
        ];
    }

    private function safe(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Exception $e) {
            Log::error('KobopointService exception', ['error' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    // ==================================================================
    // 1. CUSTOMERS
    // ==================================================================

    /** POST /api/v1/customers */
    public function createCustomer(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/customers", $data)
        ));
    }

    /** GET /api/v1/customers/{customerId} */
    public function getCustomer(string $customerId): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/customers/{$customerId}")
        ));
    }

    /** PUT /api/v1/customers/{customerId} */
    public function updateCustomer(string $customerId, array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->put("{$this->baseUrl}/customers/{$customerId}", $data)
        ));
    }

    /** DELETE /api/v1/customers/{customerId} */
    public function deleteCustomer(string $customerId): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->delete("{$this->baseUrl}/customers/{$customerId}")
        ));
    }

    // ==================================================================
    // 2. VIRTUAL ACCOUNTS
    // ==================================================================

    /** POST /api/v1/virtual-accounts */
    public function createVirtualAccount($customerOrRawData, string $accountName = null, string $accountType = 'static', array $bankCodes = ['033']): array
    {
        $payload = [
            'account_type' => $accountType,
            'bank_codes'   => $bankCodes,
        ];

        if (is_array($customerOrRawData)) {
            $payload = array_merge($payload, $customerOrRawData);
        } else {
            $payload['customer_id'] = $customerOrRawData;
            $payload['account_name'] = $accountName;
        }

        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/virtual-accounts", $payload)
        ));
    }

    /** GET /api/v1/virtual-accounts */
    public function listVirtualAccounts(): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/virtual-accounts")
        ));
    }

    /** GET /api/v1/virtual-accounts/{vaId} */
    public function getVirtualAccount(string $vaId): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/virtual-accounts/{$vaId}")
        ));
    }

    /** PUT /api/v1/virtual-accounts/{vaId} */
    public function updateVirtualAccount(string $vaId, array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->put("{$this->baseUrl}/virtual-accounts/{$vaId}", $data)
        ));
    }

    /** DELETE /api/v1/virtual-accounts/{vaId} */
    public function deleteVirtualAccount(string $vaId): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->delete("{$this->baseUrl}/virtual-accounts/{$vaId}")
        ));
    }

    // ==================================================================
    // 3. TRANSFERS (PAYOUTS)
    // ==================================================================

    /** POST /api/v1/transfers */
    public function initiateTransfer(array $data): array
    {
        // Expected: bank_code, account_number, amount, narration
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/transfers", $data)
        ));
    }

    public function createVirtualAccount(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/virtual-accounts", $data)
        ));
    }

    // ==================================================================
    // KYC METHODS
    // ==================================================================

    /**
     * Unified KYC submission for PointWave compatibility.
     */
    public function submitKYC(array $data)
    {
        try {
            \Log::info("KobopointService: Submitting KYC verification", [
                'id_type' => $data['id_type'],
                'user_email' => $data['email'] ?? 'unknown'
            ]);

            if ($data['id_type'] === 'bvn') {
                $payload = [
                    'bvn' => $data['id_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'dob' => $data['date_of_birth'],
                ];
                $result = $this->verifyBVN($payload);
            } else {
                $payload = [
                    'nin' => $data['id_number'],
                ];
                $result = $this->verifyNIN($payload);
            }

            if ($result['status'] === true) {
                return [
                    'status' => 'success',
                    'message' => $result['message'] ?? 'Verification successful',
                    'data' => $result['data'] ?? []
                ];
            }

            return [
                'status' => 'error',
                'message' => $result['message'] ?? 'Verification failed'
            ];

        } catch (\Exception $e) {
            \Log::error("KobopointService KYC Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    // ==================================================================
    // 4. BANKS
    // ==================================================================

    /** GET /api/v1/banks — cached 24 hours */
    public function getBanks(): array
    {
        $cacheKey = 'kobopoint_banks_v2';

        if (Cache::has($cacheKey)) {
            return ['status' => true, 'data' => Cache::get($cacheKey), 'cached' => true];
        }

        return $this->safe(function () use ($cacheKey) {
            $result = $this->parse($this->http()->get("{$this->baseUrl}/banks"));
            if ($result['status']) {
                Cache::put($cacheKey, $result['data'], now()->addHours(24));
            }
            return $result;
        });
    }

    /** POST /api/v1/banks/verify — cached 24 hours */
    public function resolveAccount(string $bankCode, string $accountNumber): array
    {
        $cacheKey = "kobopoint_resolve_{$bankCode}_{$accountNumber}";

        if (Cache::has($cacheKey)) {
            return ['status' => true, 'data' => Cache::get($cacheKey), 'cached' => true];
        }

        return $this->safe(function () use ($bankCode, $accountNumber, $cacheKey) {
            $result = $this->parse($this->http()->post("{$this->baseUrl}/banks/verify", [
                'bank_code'      => $bankCode,
                'account_number' => $accountNumber,
            ]));
            if ($result['status']) {
                Cache::put($cacheKey, $result['data'], now()->addHours(24));
            }
            return $result;
        });
    }

    // ==================================================================
    // 5. BALANCE & FEES
    // ==================================================================

    /** GET /api/v1/balance */
    public function getBalance(): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/balance")
        ));
    }

    /** GET /api/v1/fees/preview?amount=XXXX */
    public function previewFees(int $amount, string $type = 'transfer_interbank'): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/fees/preview", ['amount' => $amount, 'type' => $type])
        ));
    }

    // ==================================================================
    // 6. TRANSACTIONS
    // ==================================================================

    /** GET /api/v1/transactions?page=1&limit=20&from=...&to=... */
    public function getTransactions(array $filters = []): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/transactions", $filters)
        ));
    }

    /** GET /api/v1/transactions/verify/{reference} */
    public function verifyTransaction(string $reference): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->get("{$this->baseUrl}/transactions/verify/{$reference}")
        ));
    }

    // ==================================================================
    // 7. KYC VERIFICATION
    // ==================================================================

    /**
     * POST /api/v1/kyc/verify-bvn
     * data: bvn, first_name, last_name, dob (YYYY-MM-DD)
     */
    public function verifyBVN(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/verify-bvn", $data)
        ));
    }

    /**
     * POST /api/v1/kyc/verify-nin
     * data: nin
     */
    public function verifyNIN(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/verify-nin", $data)
        ));
    }

    /**
     * POST /api/v1/kyc/verify-bank-account
     * data: bank_code, account_number
     */
    public function kycVerifyBankAccount(string $bankCode, string $accountNumber): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/verify-bank-account", [
                'bank_code'      => $bankCode,
                'account_number' => $accountNumber,
            ])
        ));
    }

    /**
     * POST /api/v1/kyc/face-compare — multipart (image upload)
     * $imagePath: local path to the image file
     * $extraData: any additional fields needed
     */
    public function faceCompare(string $imagePath, array $extraData = []): array
    {
        return $this->safe(function () use ($imagePath, $extraData) {
            $request = Http::timeout(60)->withHeaders($this->multipartHeaders());
            foreach ($extraData as $key => $value) {
                $request = $request->attach($key, $value);
            }
            $request = $request->attach('image', file_get_contents($imagePath), basename($imagePath));
            return $this->parse($request->post("{$this->baseUrl}/kyc/face-compare"));
        });
    }

    /**
     * POST /api/v1/kyc/liveness/initialize
     * data: any payload required (e.g. customer reference)
     */
    public function livenessInitialize(array $data = []): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/liveness/initialize", $data)
        ));
    }

    /**
     * POST /api/v1/kyc/liveness/query
     * data: reference / session ID from initialize step
     */
    public function livenessQuery(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/liveness/query", $data)
        ));
    }

    /**
     * POST /api/v1/kyc/blacklist-check
     */
    public function blacklistCheck(array $data): array
    {
        return $this->safe(fn() => $this->parse(
            $this->http()->post("{$this->baseUrl}/kyc/blacklist-check", $data)
        ));
    }
}
