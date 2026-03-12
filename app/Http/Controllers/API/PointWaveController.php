<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PointWaveService;
use App\Models\PointWaveVirtualAccount;
use App\Models\PointWaveTransaction;
use App\Models\PointWaveKYC;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PointWaveController extends Controller
{
    protected $pointWaveService;

    public function __construct(PointWaveService $pointWaveService)
    {
        $this->middleware('auth:sanctum');
        $this->pointWaveService = $pointWaveService;
    }

    /**
     * Get user's virtual account
     * GET /api/pointwave/virtual-account
     */
    public function getVirtualAccount(Request $request)
    {
        try {
            $user = $request->user();
            
            $virtualAccount = PointWaveVirtualAccount::where('user_id', $user->id)->first();
            
            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'No virtual account found. Please create one.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $virtualAccount,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Get virtual account error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve virtual account',
            ], 500);
        }
    }

    /**
     * Create virtual account for user
     * POST /api/pointwave/virtual-account/create
     */
    public function createVirtualAccount(Request $request)
    {
        try {
            $user = $request->user();
            
            // Check if user already has a virtual account
            $existingAccount = PointWaveVirtualAccount::where('user_id', $user->id)->first();
            if ($existingAccount) {
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual account already exists',
                    'data' => $existingAccount,
                ]);
            }

            // Step 1: Create customer in PointWave
            $nameParts = explode(' ', $user->name ?? 'User Account', 2);
            $customerData = [
                'email' => $user->email,
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? 'Account',
                'phone_number' => $user->phone ?? '09000000000',
                'bvn' => $request->bvn ?? '22222222222', // Optional BVN
            ];

            Log::channel('pointwave')->info('Creating customer for user', [
                'user_id' => $user->id,
                'email' => $customerData['email'],
            ]);

            $customerResult = $this->pointWaveService->createCustomer($customerData);

            if (!$customerResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $customerResult['error'] ?? 'Failed to create customer',
                ], 400);
            }

            $customerId = $customerResult['data']['customer_id'] ?? null;
            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID not returned from API',
                ], 400);
            }

            // Step 2: Create virtual account for the customer
            $accountData = [
                'customer_id' => $customerId,
                'account_name' => $user->name ?? ($customerData['first_name'] . ' ' . $customerData['last_name']),
                'account_type' => 'static', // static or dynamic
            ];

            Log::channel('pointwave')->info('Creating virtual account for customer', [
                'user_id' => $user->id,
                'customer_id' => $customerId,
            ]);

            $accountResult = $this->pointWaveService->createVirtualAccount($accountData);

            if (!$accountResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $accountResult['error'] ?? 'Failed to create virtual account',
                ], 400);
            }

            // Extract account details from response
            $responseData = $accountResult['data'];
            
            // Store in database
            $virtualAccount = PointWaveVirtualAccount::create([
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'account_number' => $responseData['account_number'] ?? null,
                'account_name' => $responseData['account_name'] ?? $accountData['account_name'],
                'bank_name' => $responseData['bank_name'] ?? 'PalmPay',
                'bank_code' => $responseData['bank_code'] ?? '100033',
                'status' => 'active',
                'external_reference' => $responseData['reference'] ?? null,
            ]);

            Log::channel('pointwave')->info('Virtual account created', [
                'user_id' => $user->id,
                'customer_id' => $virtualAccount->customer_id,
                'account_number' => $virtualAccount->account_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual account created successfully',
                'data' => $virtualAccount,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Create virtual account error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create virtual account',
            ], 500);
        }
    }

    /**
     * Get list of supported banks
     * GET /api/pointwave/banks
     */
    public function getBanks(Request $request)
    {
        try {
            $result = $this->pointWaveService->getBanks();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to get banks',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'cached' => $result['cached'] ?? false,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Get banks error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve banks',
            ], 500);
        }
    }

    /**
     * Verify bank account
     * POST /api/pointwave/verify-account
     */
    public function verifyAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|digits:10',
            'bank_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->verifyBankAccount(
                $request->account_number,
                $request->bank_code
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to verify account',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'cached' => $result['cached'] ?? false,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Verify account error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify account',
            ], 500);
        }
    }

    /**
     * Initiate bank transfer
     * POST /api/pointwave/transfer
     */
    public function initiateTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100|max:5000000',
            'account_number' => 'required|digits:10',
            'bank_code' => 'required|string',
            'narration' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $amount = $request->amount;
            $fee = 50; // ₦50 transfer fee
            $totalDeduction = $amount + $fee;

            // Check wallet balance
            if ($user->bal < $totalDeduction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. You need ₦' . number_format($totalDeduction, 2) . ' (₦' . number_format($amount, 2) . ' + ₦50 fee)',
                ], 422);
            }

            // Check KYC tier limits
            $kyc = PointWaveKYC::where('user_id', $user->id)->first();
            $dailyLimit = $kyc && $kyc->tier === 'tier_3' ? 5000000 : 300000;

            if ($amount > $dailyLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfer exceeds your daily limit of ₦' . number_format($dailyLimit, 2) . '. Please complete KYC verification to increase your limit.',
                ], 422);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Deduct from wallet
                $user->decrement('bal', $totalDeduction);

                // Generate reference
                $reference = 'PW-' . time() . '-' . $user->id;

                // Create pending transaction record
                $transaction = PointWaveTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'transfer',
                    'amount' => $amount,
                    'fee' => $fee,
                    'status' => 'pending',
                    'reference' => $reference,
                    'account_number' => $request->account_number,
                    'bank_code' => $request->bank_code,
                    'account_name' => $request->account_name ?? null,
                    'narration' => $request->narration ?? 'Transfer',
                ]);

                // Call PointWave API
                $result = $this->pointWaveService->initiateTransfer([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'account_number' => $request->account_number,
                    'bank_code' => $request->bank_code,
                    'account_name' => $request->account_name ?? null,
                    'narration' => $request->narration ?? 'Transfer',
                    'reference' => $reference,
                ]);

                if (!$result['success']) {
                    // Rollback: refund wallet and mark transaction as failed
                    $user->increment('bal', $totalDeduction);
                    $transaction->update(['status' => 'failed', 'narration' => $result['error'] ?? 'Transfer failed']);
                    
                    DB::commit();

                    return response()->json([
                        'success' => false,
                        'message' => $result['error'] ?? 'Failed to initiate transfer',
                    ], 400);
                }

                // Update transaction with PointWave transaction ID
                $responseData = $result['data']['data'] ?? $result['data'];
                $transaction->update([
                    'pointwave_transaction_id' => $responseData['transaction_id'] ?? null,
                ]);

                DB::commit();

                Log::channel('pointwave')->info('Transfer initiated', [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $amount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transfer initiated successfully',
                    'data' => $transaction,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Transfer initiation error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate transfer',
            ], 500);
        }
    }

    /**
     * Get user's transaction history
     * GET /api/pointwave/transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = PointWaveTransaction::where('user_id', $user->id);

            // Apply filters
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate
            $perPage = $request->get('per_page', 20);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Get transactions error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
            ], 500);
        }
    }

    /**
     * Get single transaction by reference
     * GET /api/pointwave/transactions/{reference}
     */
    public function getTransaction(Request $request, $reference)
    {
        try {
            $user = $request->user();
            
            $transaction = PointWaveTransaction::where('user_id', $user->id)
                ->where('reference', $reference)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Get transaction error', [
                'user_id' => $request->user()->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction',
            ], 500);
        }
    }

    /**
     * Submit KYC information
     * POST /api/pointwave/kyc/submit
     */
    public function submitKYC(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type' => 'required|in:bvn,nin',
            'id_number' => 'required|digits:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Check if KYC already exists and is verified
            $existingKYC = PointWaveKYC::where('user_id', $user->id)->first();
            if ($existingKYC && $existingKYC->kyc_status === 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'KYC already verified',
                ], 400);
            }

            // Create or update KYC record
            $kyc = PointWaveKYC::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_type' => $request->id_type,
                    'id_number' => $request->id_number, // Will be encrypted by model mutator
                    'kyc_status' => 'pending',
                    'tier' => 'tier_1',
                    'daily_limit' => 300000.00,
                ]
            );

            Log::channel('pointwave')->info('KYC submitted', [
                'user_id' => $user->id,
                'id_type' => $request->id_type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC information submitted successfully. Verification is pending.',
                'data' => [
                    'kyc_status' => $kyc->kyc_status,
                    'tier' => $kyc->tier,
                    'daily_limit' => $kyc->daily_limit,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Submit KYC error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit KYC information',
            ], 500);
        }
    }

    /**
     * Verify user's KYC (Admin only)
     * POST /api/pointwave/kyc/verify/{userId}
     */
    public function verifyKYC(Request $request, int $userId)
    {
        try {
            // Find KYC record
            $kyc = PointWaveKYC::where('user_id', $userId)->first();

            if (!$kyc) {
                return response()->json([
                    'success' => false,
                    'message' => 'No KYC record found for this user',
                ], 404);
            }

            if ($kyc->kyc_status === 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'KYC already verified',
                ], 400);
            }

            // Update KYC to verified with tier 3 limits
            $kyc->update([
                'kyc_status' => 'verified',
                'tier' => 'tier_3',
                'daily_limit' => 5000000.00,
                'verified_at' => now(),
            ]);

            Log::channel('security')->info('KYC verified by admin', [
                'user_id' => $userId,
                'admin_id' => $request->user()->id,
                'tier' => 'tier_3',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC verified successfully',
                'data' => [
                    'kyc_status' => $kyc->kyc_status,
                    'tier' => $kyc->tier,
                    'daily_limit' => $kyc->daily_limit,
                    'verified_at' => $kyc->verified_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('KYC verification error', [
                'user_id' => $userId,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify KYC',
            ], 500);
        }
    }

    /**
     * Reject user's KYC (Admin only)
     * POST /api/pointwave/kyc/reject/{userId}
     */
    public function rejectKYC(Request $request, int $userId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find KYC record
            $kyc = PointWaveKYC::where('user_id', $userId)->first();

            if (!$kyc) {
                return response()->json([
                    'success' => false,
                    'message' => 'No KYC record found for this user',
                ], 404);
            }

            // Update KYC to rejected
            $kyc->update([
                'kyc_status' => 'rejected',
            ]);

            Log::channel('security')->info('KYC rejected by admin', [
                'user_id' => $userId,
                'admin_id' => $request->user()->id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KYC rejected',
                'data' => [
                    'kyc_status' => $kyc->kyc_status,
                    'reason' => $request->reason,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('KYC rejection error', [
                'user_id' => $userId,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject KYC',
            ], 500);
        }
    }

    /**
     * Get user's KYC status
     * GET /api/pointwave/kyc/status
     */
    public function getKYCStatus(Request $request)
    {
        try {
            $user = $request->user();
            
            $kyc = PointWaveKYC::where('user_id', $user->id)->first();

            if (!$kyc) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'kyc_status' => 'not_submitted',
                        'tier' => 'tier_1',
                        'daily_limit' => 300000.00,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'kyc_status' => $kyc->kyc_status,
                    'tier' => $kyc->tier,
                    'daily_limit' => $kyc->daily_limit,
                    'verified_at' => $kyc->verified_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Get KYC status error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve KYC status',
            ], 500);
        }
    }

    /**
     * KYC: Enhanced BVN Verification
     * POST /api/pointwave/kyc/verify-bvn
     */
    public function verifyBVNEnhanced(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn' => 'required|digits:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->verifyBVNEnhanced($request->bvn);

            if ($result['success']) {
                // Log successful verification
                Log::channel('pointwave')->info('BVN verification successful', [
                    'user_id' => $request->user()->id ?? null,
                    'bvn' => substr($request->bvn, 0, 3) . '****',
                    'charged' => $result['data']['charged'] ?? false,
                ]);

                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('BVN verification error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'BVN verification failed',
            ], 500);
        }
    }

    /**
     * KYC: Enhanced NIN Verification
     * POST /api/pointwave/kyc/verify-nin
     */
    public function verifyNINEnhanced(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nin' => 'required|digits:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->verifyNINEnhanced($request->nin);

            if ($result['success']) {
                Log::channel('pointwave')->info('NIN verification successful', [
                    'user_id' => $request->user()->id ?? null,
                    'nin' => substr($request->nin, 0, 3) . '****',
                    'charged' => $result['data']['charged'] ?? false,
                ]);

                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('NIN verification error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'NIN verification failed',
            ], 500);
        }
    }

    /**
     * KYC: Face Recognition
     * POST /api/pointwave/kyc/face-compare
     */
    public function verifyFaceRecognition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_image' => 'required|string',
            'target_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->verifyFaceRecognition(
                $request->source_image,
                $request->target_image
            );

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Face recognition error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face recognition failed',
            ], 500);
        }
    }

    /**
     * KYC: Initialize Liveness Detection
     * POST /api/pointwave/kyc/liveness/initialize
     */
    public function initializeLiveness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'biz_id' => 'required|string',
            'redirect_url' => 'required|url',
            'user_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->initializeLiveness(
                $request->biz_id,
                $request->redirect_url,
                $request->user_id
            );

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Liveness initialization error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Liveness initialization failed',
            ], 500);
        }
    }

    /**
     * KYC: Query Liveness Detection Result
     * POST /api/pointwave/kyc/liveness/query
     */
    public function queryLiveness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->queryLiveness($request->transaction_id);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Liveness query error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Liveness query failed',
            ], 500);
        }
    }

    /**
     * KYC: Blacklist Check
     * POST /api/pointwave/kyc/blacklist-check
     */
    public function checkBlacklist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'nullable|string',
            'bvn' => 'nullable|digits:11',
            'nin' => 'nullable|digits:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (!$request->phone_number && !$request->bvn && !$request->nin) {
            return response()->json([
                'success' => false,
                'message' => 'At least one identifier (phone_number, bvn, or nin) is required',
            ], 422);
        }

        try {
            $result = $this->pointWaveService->checkBlacklist(
                $request->phone_number,
                $request->bvn,
                $request->nin
            );

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Blacklist check error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Blacklist check failed',
            ], 500);
        }
    }

    /**
     * KYC: Get Credit Score
     * POST /api/pointwave/kyc/credit-score
     */
    public function getCreditScore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_no' => 'required|string',
            'id_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->getCreditScore(
                $request->mobile_no,
                $request->id_number
            );

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Credit score query error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Credit score query failed',
            ], 500);
        }
    }

    /**
     * KYC: Get Loan Features
     * POST /api/pointwave/kyc/loan-features
     */
    public function getLoanFeatures(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
            'type' => 'nullable|integer',
            'access_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $result = $this->pointWaveService->getLoanFeatures(
                $request->value,
                $request->type ?? 1,
                $request->access_type ?? '01'
            );

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('Loan features query error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Loan features query failed',
            ], 500);
        }
    }

    /**
     * KYC: Get EaseID Balance
     * GET /api/pointwave/kyc/easeid-balance
     */
    public function getEaseIDBalance(Request $request)
    {
        try {
            $result = $this->pointWaveService->getEaseIDBalance();

            if ($result['success']) {
                return response()->json($result['data']);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        } catch (\Exception $e) {
            Log::channel('pointwave')->error('EaseID balance query error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get EaseID balance',
            ], 500);
        }
    }
}
