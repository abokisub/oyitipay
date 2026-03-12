<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PointWaveService;
use Illuminate\Http\Request;

class PointWaveTestController extends Controller
{
    private $pointWave;

    public function __construct()
    {
        $this->pointWave = new PointWaveService();
    }

    /**
     * Test all PointWave endpoints
     */
    public function testAll()
    {
        $results = [];

        // Test 1: Connection Test (Wallet Balance)
        $results['1_connection_test'] = $this->pointWave->testConnection();

        // Test 2: Get Wallet Balance
        $results['2_wallet_balance'] = $this->pointWave->getWalletBalance();

        // Test 3: Get Banks List
        $results['3_banks_list'] = $this->pointWave->getBanks();

        // Test 4: Verify Bank Account (using a test account)
        // Using GTBank (058) with a sample account number
        $results['4_verify_account'] = $this->pointWave->verifyBankAccount('0123456789', '058');

        // Test 5: Get Transactions
        $results['5_transactions'] = $this->pointWave->getTransactions([
            'limit' => 10,
            'page' => 1
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'PointWave API Tests Completed',
            'results' => $results,
            'summary' => [
                'connection' => $results['1_connection_test']['success'] ?? false,
                'wallet_balance' => $results['2_wallet_balance']['success'] ?? false,
                'banks_list' => $results['3_banks_list']['success'] ?? false,
                'verify_account' => $results['4_verify_account']['success'] ?? false,
                'transactions' => $results['5_transactions']['success'] ?? false,
            ]
        ], 200);
    }

    /**
     * Test connection only
     */
    public function testConnection()
    {
        $result = $this->pointWave->testConnection();

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Connection successful' : 'Connection failed',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get wallet balance
     */
    public function getBalance()
    {
        $result = $this->pointWave->getWalletBalance();

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Balance retrieved' : 'Failed to get balance',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get banks list
     */
    public function getBanks()
    {
        $result = $this->pointWave->getBanks();

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Banks retrieved' : 'Failed to get banks',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Verify bank account
     */
    public function verifyAccount(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string|size:10',
            'bank_code' => 'required|string'
        ]);

        $result = $this->pointWave->verifyBankAccount(
            $request->account_number,
            $request->bank_code
        );

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Account verified' : 'Failed to verify account',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get transactions
     */
    public function getTransactions(Request $request)
    {
        $filters = $request->only(['page', 'limit', 'type', 'status', 'start_date', 'end_date']);
        
        $result = $this->pointWave->getTransactions($filters);

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Transactions retrieved' : 'Failed to get transactions',
            'data' => $result
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Create test virtual account
     */
    public function createTestVirtualAccount()
    {
        $testData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . time() . '@kobopoint.com',
            'phone_number' => '080' . rand(10000000, 99999999),
            'account_type' => 'static',
            'external_reference' => 'TEST-' . time()
        ];

        $result = $this->pointWave->createVirtualAccount($testData);

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['success'] ? 'Virtual account created' : 'Failed to create virtual account',
            'data' => $result,
            'test_data_used' => $testData
        ], $result['success'] ? 201 : 500);
    }
}
