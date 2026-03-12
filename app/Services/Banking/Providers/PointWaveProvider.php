<?php

namespace App\Services\Banking\Providers;

use App\Services\Banking\BankingProviderInterface;
use App\Services\PointWaveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointWaveProvider implements BankingProviderInterface
{
    protected $pointWaveService;

    public function __construct()
    {
        $this->pointWaveService = new PointWaveService();
    }

    public function getProviderSlug(): string
    {
        return 'pointwave';
    }

    public function getBanks(): array
    {
        try {
            $result = $this->pointWaveService->getBanks();
            
            if (!($result['success'] ?? false)) {
                Log::error('PointWaveProvider: Failed to fetch banks', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                throw new \Exception($result['error'] ?? 'Failed to fetch banks from PointWave');
            }
            
            $banks = $result['data'];
            
            return collect($banks)->map(function ($bank) {
                return [
                    'name' => $bank['name'] ?? $bank['bank_name'] ?? 'Unknown Bank',
                    'code' => $bank['code'] ?? $bank['bank_code'] ?? '',
                    'slug' => $bank['slug'] ?? strtolower(str_replace(' ', '-', $bank['name'] ?? '')),
                    'active' => true,
                    'pointwave_code' => $bank['code'] ?? $bank['bank_code'] ?? ''
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('PointWaveProvider: Failed to fetch banks: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        try {
            Log::info('PointWaveProvider: Starting account verification', [
                'account_number' => '****' . substr($accountNumber, -4),
                'bank_code' => $bankCode
            ]);
            
            $result = $this->pointWaveService->verifyBankAccount($accountNumber, $bankCode);
            
            Log::info('PointWaveProvider: PointWaveService returned', [
                'result_keys' => array_keys($result),
                'success' => $result['success'] ?? 'not set',
                'has_data' => isset($result['data']),
                'has_error' => isset($result['error'])
            ]);
            
            if ($result['success'] ?? false) {
                $data = $result['data'];
                
                Log::info('PointWaveProvider: Processing successful response', [
                    'data_keys' => is_array($data) ? array_keys($data) : 'not array',
                    'data_type' => gettype($data)
                ]);
                
                // Handle nested data structure: { success: true, data: { data: { account_name: ... } } }
                // or flat structure: { success: true, data: { account_name: ... } }
                $accountData = $data['data'] ?? $data;
                
                $accountName = $accountData['account_name'] 
                    ?? $accountData['accountName'] 
                    ?? $accountData['name'] 
                    ?? 'Unknown';
                
                Log::info('PointWaveProvider: Account name extracted', [
                    'account_name' => $accountName
                ]);
                
                return [
                    'status' => 'success',
                    'data' => [
                        'account_name' => $accountName,
                        'account_number' => $accountNumber,
                        'bank_code' => $bankCode
                    ]
                ];
            }
            
            // If success is false, log the full result
            Log::error('PointWaveProvider: Verification returned success=false', [
                'full_result' => $result
            ]);
            
            $errorMsg = $result['error'] ?? $result['message'] ?? 'Account verification failed';
            throw new \Exception($errorMsg);
        } catch (\Exception $e) {
            Log::error('PointWaveProvider: Account verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function transfer(array $details): array
    {
        try {
            $result = $this->pointWaveService->initiateTransfer([
                'amount' => $details['amount'],
                'bank_code' => $details['bank_code'],
                'account_number' => $details['account_number'],
                'account_name' => $details['account_name'],
                'narration' => $details['narration'] ?? 'Transfer',
                'reference' => $details['reference']
            ]);
            
            if ($result['success'] ?? false) {
                $data = $result['data'];
                
                // Log the full response structure to debug
                Log::info('PointWaveProvider: Transfer response structure', [
                    'data_keys' => is_array($data) ? array_keys($data) : 'not array',
                    'has_reference' => isset($data['reference']),
                    'has_data_reference' => isset($data['data']['reference']),
                    'reference_value' => $data['reference'] ?? $data['data']['reference'] ?? 'not found'
                ]);
                
                // PointWave returns success=true when transfer is initiated
                // Default to 'pending' since transfers need time to process
                $status = 'pending';
                
                // Check if data has a status field
                if (isset($data['status'])) {
                    $dataStatus = strtolower($data['status']);
                    if (in_array($dataStatus, ['success', 'successful', 'completed'])) {
                        $status = 'success';
                    } elseif (in_array($dataStatus, ['failed', 'fail', 'error'])) {
                        $status = 'fail';
                    }
                }
                
                // Extract provider reference (Session ID) - handle both flat and nested structures
                $providerReference = $data['reference'] 
                    ?? $data['data']['reference'] 
                    ?? $data['transaction_id'] 
                    ?? $data['data']['transaction_id'] 
                    ?? null;
                
                return [
                    'status' => $status,
                    'message' => $data['message'] ?? 'Transfer initiated successfully',
                    'reference' => $details['reference'],
                    'provider_reference' => $providerReference,
                    'bank_name' => $data['bank_name'] ?? null
                ];
            }
            
            return [
                'status' => 'fail',
                'message' => $result['error'] ?? 'Transfer failed'
            ];
        } catch (\Exception $e) {
            Log::error('PointWaveProvider: Transfer failed: ' . $e->getMessage());
            
            $errorMessage = $e->getMessage();
            
            // Handle specific error messages
            if (str_contains($errorMessage, 'Insufficient') || 
                str_contains($errorMessage, 'balance') ||
                str_contains($errorMessage, 'liquidity')) {
                $errorMessage = "Service temporarily unavailable. Please try again later.";
            }
            
            return [
                'status' => 'fail',
                'message' => 'Transfer failed: ' . $errorMessage
            ];
        }
    }

    public function getBalance(): float
    {
        try {
            $result = $this->pointWaveService->getWalletBalance();
            
            if ($result['success'] ?? false) {
                $data = $result['data'];
                return (float) ($data['balance'] ?? $data['available_balance'] ?? 0.0);
            }
            
            return 0.0;
        } catch (\Exception $e) {
            Log::error('PointWaveProvider: Failed to get balance: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function queryTransfer(string $reference): array
    {
        try {
            $result = $this->pointWaveService->getTransaction($reference);
            
            if ($result['success'] ?? false) {
                $data = $result['data'];
                $status = $data['status'] ?? 'unknown';
                
                // Map PointWave status to our standard status
                if (in_array(strtolower($status), ['success', 'successful', 'completed'])) {
                    $status = 'success';
                } elseif (in_array(strtolower($status), ['pending', 'processing'])) {
                    $status = 'pending';
                } else {
                    $status = 'failed';
                }
                
                return [
                    'status' => $status,
                    'message' => $data['message'] ?? 'Status retrieved',
                    'data' => $data
                ];
            }
            
            return [
                'status' => 'failed',
                'message' => $result['error'] ?? 'Query failed'
            ];
        } catch (\Exception $e) {
            Log::error('PointWaveProvider: Query transfer failed: ' . $e->getMessage());
            
            return [
                'status' => 'failed',
                'message' => 'Query failed: ' . $e->getMessage()
            ];
        }
    }
}
