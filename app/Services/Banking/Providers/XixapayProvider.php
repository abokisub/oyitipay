<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\BankingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XixapayProvider implements BankingProviderInterface
{
    protected $apiKey;
    protected $secretKey;
    protected $businessId;

    public function __construct()
    {
        $config = config('services.xixapay');
        $this->apiKey = $config['api_key'];
        // The Authorization header comes with "Bearer " prefix in config
        $this->secretKey = str_replace('Bearer ', '', $config['authorization']);
        $this->businessId = $config['business_id'];
    }

    /**
     * Internal helper to get prefixed businessId if required by certain endpoints
     */
    protected function getPrefixedBusinessId(): string
    {
        if (strpos($this->businessId, 'xixapay_') === 0) {
            return $this->businessId;
        }
        return 'xixapay_' . $this->businessId;
    }

    /**
     * Create HTTP client with optimized settings for Xixapay
     */
    protected function httpClient()
    {
        return Http::timeout(180)
            ->withOptions([
                'verify' => true,
                'http_version' => '1.1', // Force HTTP/1.1 (some servers have issues with HTTP/2)
                'connect_timeout' => 30,
                'allow_redirects' => true,
                'decode_content' => true,
            ]);
    }

    public function getProviderSlug(): string
    {
        return 'xixapay';
    }

    public function getBanks(): array
    {
        $response = Http::timeout(30)->get('https://api.xixapay.com/api/get/banks');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch banks from Xixapay: ' . $response->status());
        }

        $data = $response->json();

        // Validation of response format
        if (!isset($data['status']) || $data['status'] !== 'success' || !isset($data['data'])) {
            // If implicit success (just array), handle here if we discover that format
            // For now assuming documented format or failing
            throw new \Exception('Invalid response structure from Xixapay Banks API');
        }

        return collect($data['data'])->map(function ($bank) {
            return [
                'name' => $bank['bankName'],
                'code' => $bank['bankCode'], // Xixapay Code
                'slug' => strtolower(str_replace(' ', '-', $bank['bankName'])),
                'active' => true,
                'xixapay_code' => $bank['bankCode']
            ];
        })->values()->toArray();
    }

    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/verify/bank', [
                    'bank' => $bankCode,
                    'accountNumber' => $accountNumber
                ]);

        if (!$response->successful()) {
            throw new \Exception('Xixapay verification failed: ' . $response->body());
        }

        $data = $response->json();

        // Xixapay returns AccountName at top level
        $accountName = $data['AccountName'] ?? $data['data']['account_name'] ?? null;

        if (!$accountName) {
            throw new \Exception('Could not resolve account name from Xixapay response');
        }

        return [
            'status' => 'success',
            'data' => [
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]
        ];
    }

    public function transfer(array $details): array
    {
        // Xixapay Transfer
        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/v1/transfer', [
                    'businessId' => $this->businessId,
                    'amount' => $details['amount'],
                    'bank' => $details['bank_code'],
                    'accountNumber' => $details['account_number'],
                    'narration' => $details['narration'] ?? 'Transfer'
                ]);

        if ($response->successful()) {
            $data = $response->json();
            // Check specific status in body
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => $data['message'] ?? 'Transfer successful',
                    'reference' => $details['reference'],
                    'provider_reference' => $data['reference'] ?? null
                ];
            }

            return [
                'status' => 'fail',
                'message' => $data['message'] ?? 'Transfer failed'
            ];
        }

        return [
            'status' => 'fail',
            'message' => 'Xixapay API Error: ' . $response->body()
        ];
    }
    public function getBalance(): float
    {
        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.xixapay.com/api/get/balance'); // Assuming endpoint based on conv.

        if ($response->successful()) {
            $data = $response->json();
            return (float) ($data['balance'] ?? 0);
        }

        return 0.0;
    }

    public function queryTransfer(string $reference): array
    {
        // Xixapay status check
        // Assuming endpoint: /api/v1/transfer/status or similar.
        // Documentation not fully provided, so using best guess or standard pattern.
        // User checklist says "Correct States: initiated -> processing -> pending ... " via webhook usually.
        // But for query:

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.xixapay.com/api/v1/transfer/status', [
                    'reference' => $reference,
                    'businessId' => $this->businessId
                ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['status'] ?? 'unknown',
                'message' => $data['message'] ?? 'Status retrieved',
                'data' => $data
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Query failed: ' . $response->body()
        ];
    }

    /**
     * Verify Identity (KYC)
     * Phase 2 Requirement
     */
    public function verifyIdentity(string $idType, string $idNumber): array
    {
        \Log::info("XixapayProvider: POST to https://api.xixapay.com/api/identity/verify", [
            'id_type' => $idType,
            'id_number' => $idNumber,
            'businessId' => $this->businessId
        ]);

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/identity/verify', [
                    'businessId' => $this->businessId,
                    'id_number' => $idNumber,
                    'id_type' => $idType // 'bvn' or 'nin'
                ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'data' => $data['data'] ?? [],
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Identity verification failed',
            'full_response' => $data
        ];
    }

    /**
     * Create Customer (Phase 3)
     */
    /**
     * Create Customer (Phase 3)
     */
    public function createCustomer(array $details): array
    {
        $request = $this->httpClient()->asMultipart()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            // Content-Type is set automatically by asMultipart
        ]);

        // Attach Files
        if (isset($details['id_card']) && $details['id_card']) {
            $file = $details['id_card'];
            // Check if it's an UploadedFile instance or path
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $request->attach('id_card', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());
            }
        }

        if (isset($details['utility_bill']) && $details['utility_bill']) {
            $file = $details['utility_bill'];
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $request->attach('utility_bill', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());
            }
        }

        $response = $request->post('https://api.xixapay.com/api/customer/create', [
            'businessId' => $this->businessId,
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'email' => $details['email'],
            'phone_number' => $details['phone_number'],
            'address' => $details['address'],
            'state' => $details['state'],
            'city' => $details['city'],
            'postal_code' => $details['postal_code'],
            'date_of_birth' => $details['date_of_birth'],
            'id_type' => $details['id_type'],
            'id_number' => $details['id_number'],
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'data' => $data['data'] ?? [],
                'customer_id' => $data['data']['customer_id'] ?? $data['data']['id'] ?? $data['customer']['customer_id'] ?? null,
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Customer creation failed',
            'full_response' => $data
        ];
    }

    /**
     * Update Customer (Phase 3)
     */
    public function updateCustomer(array $details): array
    {
        $request = $this->httpClient()->asMultipart()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
        ]);

        // Attach Files (Optional for Update, but if provided they are sent)
        if (isset($details['id_card']) && $details['id_card']) {
            $file = $details['id_card'];
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $request->attach('id_card', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());
            }
        }

        if (isset($details['utility_bill']) && $details['utility_bill']) {
            $file = $details['utility_bill'];
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $request->attach('utility_bill', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());
            }
        }

        $response = $request->post('https://api.xixapay.com/api/customer/update', [
            'businessId' => $this->businessId,
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'email' => $details['email'],
            'phone_number' => $details['phone_number'],
            'address' => $details['address'],
            'state' => $details['state'],
            'city' => $details['city'],
            'postal_code' => $details['postal_code'],
            'date_of_birth' => $details['date_of_birth'],
            'id_type' => $details['id_type'],
            'id_number' => $details['id_number'],
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'data' => $data['data'] ?? [],
                'customer_id' => $data['data']['customer_id'] ?? $data['data']['id'] ?? $data['customer']['customer_id'] ?? null,
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Customer update failed',
            'full_response' => $data
        ];
    }
    /**
     * Create Virtual Card (Phase 4)
     */
    public function createVirtualCard(string $customerId, string $currency, float $amount): array
    {
        // Map Currency to Country Code as per Xixapay Docs
        $country = ($currency === 'NGN') ? 'NG' : 'US';

        \Log::info("XixapayProvider: Attempting to create virtual card", [
            'customer_id' => $customerId,
            'currency' => $currency,
            'amount' => $amount,
            'businessId' => $this->businessId,
            'country' => $country
        ]);

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/card/create', [
                    'businessId' => $this->getPrefixedBusinessId(), // Uses prefix
                    'customer_id' => $customerId,
                    'country' => $country,
                    'amount' => $amount
                ]);

        $data = $response->json();

        \Log::info("XixapayProvider: Card creation response", [
            'status' => $response->status(),
            'body' => $data
        ]);

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'data' => $data['data'] ?? [],
                'card_id' => $data['data']['card_id'] ?? $data['data']['id'] ?? null,
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Card creation failed',
            'full_response' => $data
        ];
    }
    /**
     * Fund Virtual Card (Phase 5)
     */
    /**
     * Fund Virtual Card (Phase 5)
     * Endpoint: POST /api/card/{id}/fund
     */
    public function fundVirtualCard(string $cardId, float $amount): array
    {
        \Log::info("XixapayProvider: Attempting to fund virtual card", [
            'card_id' => $cardId,
            'amount' => $amount,
            'businessId' => $this->getPrefixedBusinessId()
        ]);

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post("https://api.xixapay.com/api/card/{$cardId}/fund", [
                    'businessId' => $this->getPrefixedBusinessId(),
                    'amount' => $amount
                ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? 'Card funded successfully',
                'data' => $data['data'] ?? [],
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Card funding failed',
            'full_response' => $data
        ];
    }

    /**
     * Withdraw from Virtual Card (Phase 5)
     */
    /**
     * Withdraw from Virtual Card (Phase 5)
     * Endpoint: POST /api/cards/{card_id}/withdraw
     */
    public function withdrawVirtualCard(string $cardId, float $amount): array
    {
        \Log::info("XixapayProvider: Attempting to withdraw from virtual card", [
            'card_id' => $cardId,
            'amount' => $amount,
            'businessId' => $this->getPrefixedBusinessId()
        ]);

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post("https://api.xixapay.com/api/cards/{$cardId}/withdraw", [
                    'businessId' => $this->getPrefixedBusinessId(),
                    'amount' => $amount
                ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? 'Withdrawal successful',
                'data' => $data['data'] ?? [],
                'transaction_id' => $data['transaction_id'] ?? null,
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Card withdrawal failed',
            'full_response' => $data
        ];
    }

    /**
     * Change Card Status (Phase 5)
     * Endpoint: PUT /api/card/{id}/status
     * Status: 'active', 'frozen', 'blocked'
     */
    public function changeCardStatus(string $cardId, string $status): array
    {
        // Enforce valid actions
        $validStatuses = ['active', 'frozen', 'blocked'];
        if (!in_array($status, $validStatuses)) {
            return ['status' => 'error', 'message' => "Invalid status. Allowed: " . implode(', ', $validStatuses)];
        }

        \Log::info("XixapayProvider: Attempting to change card status", [
            'card_id' => $cardId,
            'status' => $status,
            'businessId' => $this->getPrefixedBusinessId()
        ]);

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->put("https://api.xixapay.com/api/card/{$cardId}/status", [
                    'businessId' => $this->getPrefixedBusinessId(),
                    'status' => $status
                ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? "Card status updated to $status",
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? "Failed to update card status"
        ];
    }
    /**
     * Create Virtual Account (Bank Transfer)
     * Supports multiple bank codes (e.g., PalmPay + Kolomoni)
     */
    public function createVirtualAccount(array $payload): array
    {
        // $payload should look like:
        // [
        //    'email' => ..., 'name' => ..., 'phoneNumber' => ..., 'bankCode' => ['20867', '20987'],
        //    'accountType' => 'static', 'businessId' => ...
        // ]
        // OR
        // [ 'customer_id' => ..., 'bankCode' => ..., 'accountType' => ... ]

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', $payload);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? 'Virtual account created',
                'customer' => $data['customer'] ?? [],
                'bankAccounts' => $data['bankAccounts'] ?? [], // Array of accounts
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Virtual account creation failed'
        ];
    }
    /**
     * Update Virtual Account Status
     * Endpoint: PATCH /api/v1/updateVirtualAccountStatus
     */
    public function updateVirtualAccountStatus(string $accountNumber, string $status, string $reason = null): array
    {
        $payload = [
            'businessId' => $this->businessId,
            'accountNumber' => $accountNumber,
            'status' => $status
        ];

        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->patch('https://api.xixapay.com/api/v1/updateVirtualAccountStatus', $payload);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? 'Account status updated successfully',
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Failed to update account status'
        ];
    }
    /**
     * Get Card Details & Balance
     * Endpoint: POST /api/card/{card_id}/balance
     */
    public function getCardDetails(string $cardId): array
    {
        $response = $this->httpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post("https://api.xixapay.com/api/card/{$cardId}/balance", [
                    'businessId' => $this->getPrefixedBusinessId()
                ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] === 'success' || $data['status'] === true)) {
            return [
                'status' => 'success',
                'message' => $data['message'] ?? 'Card details retrieved',
                'data' => $data['data'] ?? [],
                'full_response' => $data
            ];
        }

        return [
            'status' => 'error',
            'message' => $data['message'] ?? 'Failed to retrieve card details'
        ];
    }
}

