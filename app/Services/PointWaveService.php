<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PointWaveService
{
    private $baseUrl;
    private $secretKey;
    private $apiKey;
    private $businessId;
    private $verifySSL;

    public function __construct()
    {
        // Always fallback to env() if config is not cached to prevent 401 errors after config:clear
        $this->baseUrl = config('pointwave.base_url') ?? env('POINTWAVE_BASE_URL', 'https://app.pointwave.ng/api/v1');
        $this->secretKey = config('pointwave.secret_key') ?? env('POINTWAVE_SECRET_KEY');
        $this->apiKey = config('pointwave.api_key') ?? env('POINTWAVE_API_KEY');
        $this->businessId = config('pointwave.business_id') ?? env('POINTWAVE_BUSINESS_ID');
        $this->verifySSL = env('POINTWAVE_VERIFY_SSL', true);
    }
    
    /**
     * Get HTTP client with SSL verification setting and optional custom timeout
     */
    private function getHttpClient($timeout = 30)
    {
        $client = Http::timeout($timeout);
        
        if (!$this->verifySSL) {
            $client = $client->withoutVerifying();
        }
        
        return $client;
    }

    /**
     * Get default headers for PointWave API v1 requests
     */
    private function getHeaders($includeIdempotency = false)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'x-api-key' => $this->apiKey,
            'x-business-id' => $this->businessId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($includeIdempotency) {
            $headers['Idempotency-Key'] = Str::uuid()->toString();
        }

        return $headers;
    }

    /**
     * Retry a request with exponential backoff
     */
    private function retryRequest(callable $request, int $maxRetries = 2)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                $result = $request();
                
                // If successful or client error (4xx), don't retry
                if (isset($result['success']) && ($result['success'] || (isset($result['status']) && $result['status'] < 500))) {
                    return $result;
                }
                
                // If server error (5xx), retry
                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt); // Exponential backoff: 1s, 2s
                    Log::channel('pointwave')->warning('Retrying request', [
                        'attempt' => $attempt + 1,
                        'wait_seconds' => $waitTime,
                    ]);
                    sleep($waitTime);
                }
                
                $attempt++;
            } catch (\Exception $e) {
                $lastException = $e;
                
                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt);
                    Log::channel('pointwave')->warning('Retrying after exception', [
                        'attempt' => $attempt + 1,
                        'wait_seconds' => $waitTime,
                        'error' => $e->getMessage(),
                    ]);
                    sleep($waitTime);
                }
                
                $attempt++;
            }
        }

        // All retries failed
        if ($lastException) {
            return $this->handleApiError($lastException);
        }

        return $result ?? ['success' => false, 'error' => 'Request failed after retries'];
    }

    /**
     * Handle API errors and format error responses
     */
    private function handleApiError(\Exception $e)
    {
        $errorMessage = $e->getMessage();
        
        Log::channel('pointwave')->error('PointWave API Error', [
            'error' => $errorMessage,
            'trace' => $e->getTraceAsString(),
        ]);

        // Check for specific error types
        if (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
            return [
                'success' => false,
                'error' => 'Request timed out. Please try again.',
                'error_code' => 'TIMEOUT',
            ];
        }

        if (str_contains($errorMessage, 'Connection refused') || str_contains($errorMessage, 'Could not resolve host')) {
            return [
                'success' => false,
                'error' => 'Service temporarily unavailable. Please try again later.',
                'error_code' => 'CONNECTION_ERROR',
            ];
        }

        return [
            'success' => false,
            'error' => 'An error occurred while processing your request.',
            'error_code' => 'API_ERROR',
            'details' => config('app.debug') ? $errorMessage : null,
        ];
    }

    /**
     * Mask sensitive data in logs
     */
    private function maskSensitiveData(array $data)
    {
        $masked = $data;
        
        // Mask API keys
        if (isset($masked['api_key'])) {
            $masked['api_key'] = substr($masked['api_key'], 0, 8) . '...';
        }
        
        // Mask account numbers
        if (isset($masked['account_number'])) {
            $masked['account_number'] = '****' . substr($masked['account_number'], -4);
        }
        
        // Mask BVN/NIN
        if (isset($masked['id_number'])) {
            $masked['id_number'] = '****' . substr($masked['id_number'], -4);
        }
        
        // Mask secret key
        if (isset($masked['Authorization'])) {
            $masked['Authorization'] = 'Bearer ' . substr(str_replace('Bearer ', '', $masked['Authorization']), 0, 10) . '...';
        }
        
        return $masked;
    }

    /**
     * Test API connection by getting wallet balance
     */
    public function testConnection()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/balance');

            Log::info('PointWave Test Connection', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('PointWave Connection Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get wallet balance
     */
    public function getWalletBalance()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get balance',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of supported banks (with 24-hour caching)
     */
    public function getBanks()
    {
        $cacheKey = 'pointwave_banks';
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            Log::channel('pointwave')->info('Banks retrieved from cache');
            return [
                'success' => true,
                'data' => Cache::get($cacheKey),
                'cached' => true,
            ];
        }

        return $this->retryRequest(function () use ($cacheKey) {
            $startTime = microtime(true);
            $requestId = Str::uuid()->toString();
            
            try {
                Log::channel('pointwave')->info('Fetching banks from API', [
                    'request_id' => $requestId,
                ]);

                $response = $this->getHttpClient()
                    ->withHeaders($this->getHeaders())
                    ->get($this->baseUrl . '/banks');

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('pointwave')->info('Banks API response', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];
                    
                    // Cache for 24 hours
                    Cache::put($cacheKey, $data, now()->addHours(24));
                    
                    return [
                        'success' => true,
                        'data' => $data,
                        'cached' => false,
                    ];
                }

                // Handle specific HTTP errors
                if ($response->status() === 401) {
                    Log::channel('pointwave')->error('Invalid credentials', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Invalid API credentials',
                        'error_code' => 'INVALID_CREDENTIALS',
                        'status' => 401,
                    ];
                }

                if ($response->status() === 429) {
                    Log::channel('pointwave')->warning('Rate limit exceeded', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Too many requests. Please try again later.',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'status' => 429,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to get banks',
                    'status' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Banks API error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Verify bank account (with 24-hour caching)
     */
    public function verifyBankAccount($accountNumber, $bankCode)
    {
        $cacheKey = "pointwave_verify_{$accountNumber}_{$bankCode}";
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            Log::channel('pointwave')->info('Account verification retrieved from cache', [
                'account_number' => '****' . substr($accountNumber, -4),
                'bank_code' => $bankCode,
            ]);
            return [
                'success' => true,
                'data' => Cache::get($cacheKey),
                'cached' => true,
            ];
        }

        return $this->retryRequest(function () use ($accountNumber, $bankCode, $cacheKey) {
            $startTime = microtime(true);
            $requestId = Str::uuid()->toString();
            
            try {
                Log::channel('pointwave')->info('Verifying bank account', [
                    'request_id' => $requestId,
                    'account_number' => '****' . substr($accountNumber, -4),
                    'bank_code' => $bankCode,
                ]);

                $response = $this->getHttpClient()
                    ->withHeaders($this->getHeaders(true))
                    ->post($this->baseUrl . '/banks/verify', [
                        'account_number' => $accountNumber,
                        'bank_code' => $bankCode
                    ]);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                Log::info('PointWave API Response Details', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'response_body' => $response->body(),
                    'response_json' => $response->json()
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Cache for 24 hours
                    Cache::put($cacheKey, $data, now()->addHours(24));
                    
                    return [
                        'success' => true,
                        'data' => $data,
                        'cached' => false,
                    ];
                }

                // Handle specific errors
                if ($response->status() === 401) {
                    Log::channel('pointwave')->error('Invalid credentials', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Invalid API credentials',
                        'error_code' => 'INVALID_CREDENTIALS',
                        'status' => 401,
                    ];
                }

                $responseData = $response->json();
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to verify account';
                
                Log::error('PointWave verification failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'error_message' => $errorMessage,
                    'full_response' => $responseData
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Account verification error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create customer in PointWave
     */
    public function createCustomer($data)
    {
        return $this->retryRequest(function () use ($data) {
            $startTime = microtime(true);
            $requestId = Str::uuid()->toString();
            
            try {
                // Build payload according to PointWave API v1 documentation
                $payload = [
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone_number' => $data['phone_number'], // Note: phone_number not phone
                    'bvn' => $data['bvn'] ?? '22222222222', // Default BVN if not provided
                ];

                Log::channel('pointwave')->info('Creating customer', [
                    'request_id' => $requestId,
                    'email' => $payload['email'],
                    'phone_number' => $payload['phone_number'],
                ]);

                $response = $this->getHttpClient()
                    ->withHeaders($this->getHeaders())
                    ->post($this->baseUrl . '/customers', $payload);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('pointwave')->info('Customer creation response', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    Log::channel('pointwave')->info('Customer created successfully', [
                        'request_id' => $requestId,
                        'customer_id' => $responseData['data']['customer_id'] ?? null,
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData['data'] ?? $responseData,
                    ];
                }

                // Handle specific errors
                if ($response->status() === 401) {
                    Log::channel('pointwave')->error('Invalid credentials', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Invalid API credentials',
                        'error_code' => 'INVALID_CREDENTIALS',
                        'status' => 401,
                    ];
                }

                Log::channel('pointwave')->error('Customer creation failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to create customer',
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Customer creation error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create virtual account for customer
     */
    public function createVirtualAccount($data)
    {
        return $this->retryRequest(function () use ($data) {
            $startTime = microtime(true);
            $requestId = Str::uuid()->toString();
            
            try {
                // Build payload according to PointWave API v1 documentation
                // Requires: customer_id, account_name, account_type
                $payload = [
                    'customer_id' => $data['customer_id'],
                    'account_name' => $data['account_name'],
                    'account_type' => $data['account_type'] ?? 'static', // static or dynamic
                ];

                Log::channel('pointwave')->info('Creating virtual account', [
                    'request_id' => $requestId,
                    'customer_id' => $payload['customer_id'],
                    'account_type' => $payload['account_type'],
                ]);

                $response = $this->getHttpClient()
                    ->withHeaders($this->getHeaders(true)) // Include Idempotency-Key
                    ->post($this->baseUrl . '/virtual-accounts', $payload);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('pointwave')->info('Virtual account creation response', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    Log::channel('pointwave')->info('Virtual account created successfully', [
                        'request_id' => $requestId,
                        'response' => $responseData,
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData['data'] ?? $responseData,
                    ];
                }

                // Handle specific errors
                if ($response->status() === 401) {
                    Log::channel('pointwave')->error('Invalid credentials', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Invalid API credentials',
                        'error_code' => 'INVALID_CREDENTIALS',
                        'status' => 401,
                    ];
                }

                Log::channel('pointwave')->error('Virtual account creation failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to create virtual account',
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Virtual account creation error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Initiate bank transfer
     */
    public function initiateTransfer($data)
    {
        return $this->retryRequest(function () use ($data) {
            $startTime = microtime(true);
            $requestId = Str::uuid()->toString();
            
            try {
                // Generate reference if not provided
                $reference = $data['reference'] ?? 'PW-' . time() . '-' . ($data['user_id'] ?? Str::random(6));
                
                $payload = [
                    'amount' => $data['amount'],
                    'account_number' => $data['account_number'],
                    'bank_code' => $data['bank_code'],
                    'narration' => $data['narration'] ?? 'Transfer',
                    'reference' => $reference,
                ];

                if (isset($data['account_name'])) {
                    $payload['account_name'] = $data['account_name'];
                }

                Log::channel('pointwave')->info('Initiating transfer', [
                    'request_id' => $requestId,
                    'reference' => $reference,
                    'amount' => $data['amount'],
                    'account_number' => '****' . substr($data['account_number'], -4),
                    'bank_code' => $data['bank_code'],
                ]);

                $response = $this->getHttpClient()
                    ->withHeaders($this->getHeaders(true))
                    ->post($this->baseUrl . '/transfers', $payload);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                Log::channel('pointwave')->info('Transfer initiation response', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    Log::channel('pointwave')->info('Transfer initiated successfully', [
                        'request_id' => $requestId,
                        'reference' => $reference,
                        'response_data' => $responseData, // Log full response to see what PointWave returns
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData,
                    ];
                }

                // Handle specific errors
                if ($response->status() === 401) {
                    Log::channel('pointwave')->error('Invalid credentials', [
                        'request_id' => $requestId,
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Invalid API credentials',
                        'error_code' => 'INVALID_CREDENTIALS',
                        'status' => 401,
                    ];
                }

                Log::channel('pointwave')->error('Transfer initiation failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->json()['message'] ?? 'Failed to initiate transfer',
                    'status' => $response->status(),
                ];
            } catch (\Exception $e) {
                Log::channel('pointwave')->error('Transfer initiation error', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get transactions
     */
    public function getTransactions($filters = [])
    {
        try {
            $queryParams = [];
            
            if (isset($filters['page'])) {
                $queryParams['page'] = $filters['page'];
            }
            if (isset($filters['limit'])) {
                $queryParams['limit'] = $filters['limit'];
            }
            if (isset($filters['type'])) {
                $queryParams['type'] = $filters['type'];
            }
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            if (isset($filters['start_date'])) {
                $queryParams['start_date'] = $filters['start_date'];
            }
            if (isset($filters['end_date'])) {
                $queryParams['end_date'] = $filters['end_date'];
            }

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/transactions', $queryParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get transactions',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get single transaction
     */
    public function getTransaction($transactionId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/transactions/' . $transactionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get transaction',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer details
     */
    public function getCustomer($customerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/customers/' . $customerId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get customer',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update customer
     */
    public function updateCustomer($customerId, $data)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/customers/' . $customerId, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to update customer',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete customer (NEW - API v1 Update)
     */
    public function deleteCustomer($customerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->delete($this->baseUrl . '/customers/' . $customerId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to delete customer',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get virtual account by ID (NEW - API v1 Update)
     */
    public function getVirtualAccount($virtualAccountId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/virtual-accounts/' . $virtualAccountId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get virtual account',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all virtual accounts (NEW - API v1 Update)
     */
    public function listVirtualAccounts($filters = [])
    {
        try {
            $queryParams = [];
            
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            if (isset($filters['page'])) {
                $queryParams['page'] = $filters['page'];
            }
            if (isset($filters['per_page'])) {
                $queryParams['per_page'] = $filters['per_page'];
            }

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/virtual-accounts', $queryParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to list virtual accounts',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete virtual account (NEW - API v1 Update)
     */
    public function deleteVirtualAccount($virtualAccountId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true)) // Include Idempotency-Key
                ->delete($this->baseUrl . '/virtual-accounts/' . $virtualAccountId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to delete virtual account',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * KYC: Enhanced BVN Verification
     * Verify BVN and get full customer details including photo
     * Cost: ₦100 per successful verification
     */
    public function verifyBVNEnhanced($bvn)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/verify-bvn', [
                    'bvn' => $bvn
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'BVN verification failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Enhanced NIN Verification
     * Verify NIN and get full customer details including photo
     * Cost: ₦100 per successful verification
     */
    public function verifyNINEnhanced($nin)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/verify-nin', [
                    'nin' => $nin
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'NIN verification failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Face Recognition
     * Compare two face images to verify if they belong to the same person
     * Cost: ₦50 per successful verification
     */
    public function verifyFaceRecognition($sourceImage, $targetImage)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/face-compare', [
                    'source_image' => $sourceImage,
                    'target_image' => $targetImage
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Face recognition failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Liveness Detection - Initialize
     * Initialize liveness detection to prevent spoofing attacks
     * Cost: ₦100 per successful verification
     */
    public function initializeLiveness($bizId, $redirectUrl, $userId = null)
    {
        try {
            $payload = [
                'biz_id' => $bizId,
                'redirect_url' => $redirectUrl
            ];

            if ($userId) {
                $payload['user_id'] = $userId;
            }

            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/liveness/initialize', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Liveness initialization failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Liveness Detection - Query Result
     * Query the result of a liveness detection
     */
    public function queryLiveness($transactionId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/liveness/query', [
                    'transaction_id' => $transactionId
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Liveness query failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Blacklist Check
     * Check if customer is on credit blacklist
     * Cost: ₦50 per successful verification
     */
    public function checkBlacklist($phoneNumber = null, $bvn = null, $nin = null)
    {
        try {
            $payload = array_filter([
                'phone_number' => $phoneNumber,
                'bvn' => $bvn,
                'nin' => $nin
            ]);

            if (empty($payload)) {
                return [
                    'success' => false,
                    'error' => 'At least one identifier (phone_number, bvn, or nin) is required'
                ];
            }

            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/blacklist-check', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Blacklist check failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Credit Score Query
     * Get customer credit score for lending decisions
     * Cost: ₦100 per successful verification
     */
    public function getCreditScore($mobileNo, $idNumber)
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/credit-score', [
                    'mobile_no' => $mobileNo,
                    'id_number' => $idNumber
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Credit score query failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Loan Features Query
     * Get customer loan history and behavior
     * Cost: ₦50 per successful verification
     */
    public function getLoanFeatures($value, $type = 1, $accessType = '01')
    {
        try {
            $response = Http::withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . '/kyc/loan-features', [
                    'value' => $value,
                    'type' => $type,
                    'access_type' => $accessType
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Loan features query failed',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * KYC: Check EaseID Balance
     * Check your EaseID account balance (for monitoring purposes)
     * Cost: Free
     */
    public function getEaseIDBalance()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/kyc/easeid-balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get EaseID balance',
                'response' => $response->json()
            ];
        } catch (\Exception $e) {
            return $this->handleApiError($e);
        }
    }

    /**
     * Submit KYC Verification
     * Verify user identity using NIN or BVN
     * 
     * @param array $data User KYC data
     * @return array Response with status and data
     */
    public function submitKYC(array $data)
    {
        try {
            \Log::info("PointWaveService: Submitting KYC verification", [
                'id_type' => $data['id_type'],
                'user_email' => $data['email']
            ]);

            // Use the correct endpoint based on ID type
            if ($data['id_type'] === 'bvn') {
                $endpoint = '/kyc/verify-bvn';
                $payload = [
                    'bvn' => $data['id_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'phone' => $data['phone'],
                ];
            } else {
                $endpoint = '/kyc/verify-nin';
                $payload = [
                    'nin' => $data['id_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'phone' => $data['phone'],
                ];
            }

            // Use 60 second timeout for KYC verification (BVN can be slow)
            $response = $this->getHttpClient(60)
                ->withHeaders($this->getHeaders(true))
                ->post($this->baseUrl . $endpoint, $payload);

            \Log::info("PointWaveService: KYC API Response", [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // PointWave returns "status": true for success (not "success": true)
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    return [
                        'status' => 'success',
                        'message' => $responseData['message'] ?? 'KYC verification successful',
                        'data' => $responseData['data'] ?? $responseData
                    ];
                }
            }

            // Handle error response
            $errorMessage = $response->json()['message'] ?? 'Identity verification failed';
            
            return [
                'status' => 'error',
                'message' => $errorMessage,
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            \Log::error("PointWaveService: KYC verification exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'KYC verification failed: ' . $e->getMessage()
            ];
        }
    }
}
