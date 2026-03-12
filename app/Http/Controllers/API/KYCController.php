<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PointWaveService;

class KYCController extends Controller
{
    protected $pointWaveService;

    public function __construct()
    {
        $this->pointWaveService = new PointWaveService();
    }

    /**
     * Check KYC Status and Return Pre-fill Data
     * GET /api/user/kyc/check
     */
    public function checkKycStatus(Request $request)
    {
        $user = DB::table('user')->where('id', $request->user()->id)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        $missingFields = $this->getMissingFields($user);

        // Split name into first and last
        $nameParts = explode(' ', $user->name ?? '');
        $firstName = $nameParts[0] ?? '';
        $lastName = implode(' ', array_slice($nameParts, 1)) ?: $firstName;

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_customer_id' => !empty($user->customer_id),
                'kyc_status' => $user->kyc_status ?? 'pending',
                'missing_fields' => $missingFields,
                'is_complete' => empty($missingFields),
                'prefill_data' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone_number' => $user->username,
                    'bvn' => $user->bvn ?? '',
                    'nin' => $user->nin ?? '',
                    'date_of_birth' => $user->dob ?? '',
                    'address' => $user->address ?? '',
                    'city' => '', // These are consolidated in address usually
                    'state' => '',
                    'postal_code' => '',
                ]
            ]
        ]);
    }

    /**
     * Determine Missing KYC Fields
     */
    private function getMissingFields($user): array
    {
        $missing = [];

        // At least one ID type required
        if (empty($user->bvn) && empty($user->nin)) {
            $missing[] = 'id_number';
        }

        // Required document uploads
        if (empty($user->id_card_path)) {
            $missing[] = 'id_card';
        }
        if (empty($user->utility_bill_path)) {
            $missing[] = 'utility_bill';
        }

        // Required fields
        $requiredFields = ['dob', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                $missing[] = $field === 'dob' ? 'date_of_birth' : $field;
            }
        }

        return $missing;
    }

    /**
     * Submit KYC and Create Xixapay Customer
     * POST /api/user/kyc/submit
     */
    public function submitKyc(Request $request)
    {
        set_time_limit(300);
        $user = DB::table('user')->where('id', $request->user()->id)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        // Check if already has customer_id
        if (!empty($user->customer_id)) {
            return response()->json([
                'status' => 'success',
                'message' => 'KYC already completed',
                'data' => ['customer_id' => $user->customer_id]
            ]);
        }

        // Base Validation
        $rules = [
            'id_type' => 'required|in:bvn,nin',
            'id_number' => 'required|digits:11',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'id_card' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'utility_bill' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            // Both DOB and phone are optional, but at least one is recommended
            'date_of_birth' => 'nullable|date|before:14 years ago',
            'phone' => 'nullable|string|regex:/^[0-9]+$/|min:10|max:15',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // Upload Files
            $idCardPath = $request->file('id_card')->store("kyc/{$user->id}", 'public');
            $utilityBillPath = $request->file('utility_bill')->store("kyc/{$user->id}", 'public');

            // Use provided values or fallback to user data
            $phoneForVerification = $request->phone ?? $user->username;
            $dobForVerification = $request->date_of_birth ?? $user->dob;

            // CHECK FOR DUPLICATE BVN/NIN
            $idType = $request->id_type;
            $idNumber = $request->id_number;
            
            $duplicateUser = DB::table('user')
                ->where($idType, $idNumber)
                ->where('id', '!=', $user->id)
                ->first();
            
            if ($duplicateUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This ' . strtoupper($idType) . ' is already registered to another account. Each ' . strtoupper($idType) . ' can only be used once.'
                ], 400);
            }

            // Determine tier and limits based on ID type
            $tierData = $this->getTierDataForIdType($request->id_type);

            // Update User Table First
            DB::table('user')->where('id', $user->id)->update([
                $request->id_type => $request->id_number,
                'dob' => $dobForVerification,
                'address' => $request->address . ', ' . $request->city . ', ' . $request->state,
                'id_card_path' => $idCardPath,
                'utility_bill_path' => $utilityBillPath,
                'kyc_documents' => json_encode([
                    'id_card' => $idCardPath,
                    'utility_bill' => $utilityBillPath,
                    'id_type' => $request->id_type,
                    'id_number' => $request->id_number,
                    'submitted_metadata' => [
                        'address' => $request->address,
                        'city' => $request->city,
                        'state' => $request->state,
                        'postal_code' => $request->postal_code,
                        'phone' => $phoneForVerification,
                        'dob' => $dobForVerification,
                    ]
                ]),
                'kyc_status' => 'submitted',
                'kyc_submitted_at' => now(),
                // SET TIER AND LIMITS
                'kyc_tier' => $tierData['tier'],
                'single_limit' => $tierData['single_limit'],
                'daily_limit' => $tierData['daily_limit']
            ]);

            // Submit KYC to PointWave
            $nameParts = explode(' ', $user->name ?? '');
            $firstName = $nameParts[0] ?? 'User';
            $lastName = implode(' ', array_slice($nameParts, 1)) ?: $firstName;

            \Log::info("KYC: Submitting to PointWave for User {$user->id} | Type: {$request->id_type} | Number: {$request->id_number}");

            $result = $this->pointWaveService->submitKYC([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'phone' => $phoneForVerification,
                'address' => $request->address . ', ' . $request->city . ', ' . $request->state,
                'date_of_birth' => $dobForVerification,
                'id_type' => $request->id_type,
                'id_number' => $request->id_number,
            ]);

            if ($result['status'] === 'success') {
                // Update user table with KYC approval
                DB::table('user')->where('id', $user->id)->update([
                    'kyc_status' => 'approved',
                    'kyc' => '1'
                ]);

                // Store in pointwave_kyc table
                DB::table('pointwave_kyc')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'id_type' => $request->id_type,
                        'id_number_encrypted' => encrypt($request->id_number),
                        'kyc_status' => 'verified',
                        'tier' => $tierData['tier'],
                        'daily_limit' => $tierData['daily_limit'],
                        'verification_response' => json_encode($result['data'] ?? []),
                        'verified_at' => now(),
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())')
                    ]
                );

                // Sync to user_kyc table for Admin Visibility
                DB::table('user_kyc')->updateOrInsert(
                    ['user_id' => $user->id, 'id_type' => $request->id_type],
                    [
                        'id_number' => $request->id_number,
                        'full_response_json' => json_encode($result['data'] ?? []),
                        'status' => 'verified',
                        'verified_at' => now(),
                        'provider' => 'pointwave',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                \Log::info("KYC: PointWave verification SUCCESS for User {$user->id}");

                return response()->json([
                    'status' => 'success',
                    'message' => 'KYC approved! Bank transfers are now enabled.',
                    'data' => [
                        'tier' => $tierData['tier'],
                        'single_limit' => $tierData['single_limit'],
                        'daily_limit' => $tierData['daily_limit']
                    ]
                ]);
            }

            $errorMessage = $result['message'] ?? 'KYC verification failed';
            \Log::warning("KYC: PointWave verification FAILED for User {$user->id}. Message: {$errorMessage}");

            throw new \Exception($errorMessage);

        } catch (\Exception $e) {
            // Mark as rejected on failure
            DB::table('user')->where('id', $user->id)->update([
                'kyc_status' => 'rejected'
            ]);

            \Log::error("KYC Submission Error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Get tier and limits based on ID type
     * NIN = tier_1, BVN = tier_2
     */
    private function getTierDataForIdType($idType)
    {
        if ($idType === 'bvn') {
            return [
                'tier' => 'tier_2',
                'single_limit' => 500000.00,
                'daily_limit' => 2000000.00
            ];
        }
        
        // NIN or default
        return [
            'tier' => 'tier_1',
            'single_limit' => 50000.00,
            'daily_limit' => 200000.00
        ];
    }
}
