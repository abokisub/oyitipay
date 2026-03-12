<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountVerification extends Controller
{
    /**
     * Verify bank account using the active transfer provider
     * Routes to Xixapay, Paystack, or Monnify based on settings
     */
    public function verifyBankAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->header('Origin');
        $authorization = $request->header('Authorization');

        if (!$origin || in_array($origin, $explode_url) || $origin === $request->getSchemeAndHttpHost() || config('app.habukhan_device_key') === $authorization) {
            if (!empty($request->id)) {
                // Verify user authentication
                $auth_user = DB::table('user')->where('status', 1)->where(function ($query) use ($request) {
                    $query->orWhere('id', $this->verifytoken($request->id))
                        ->orWhere('id', $this->verifyapptoken($request->id));
                })->first();

                if (!$auth_user) {
                    return response()->json(['message' => 'Unable to signin user', 'status' => 'fail'], 403);
                }

                // Validate input
                $bankCode = $request->input('bank_code');
                $accountNumber = $request->input('account_number');

                if (empty($bankCode) || empty($accountNumber)) {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Bank code and account number are required'
                    ], 400);
                }

                try {
                    // Get active transfer provider
                    $settings = DB::table('settings')->first();
                    $primaryProvider = $settings->primary_transfer_provider ?? 'smart_routing';

                    // If smart routing, use first unlocked provider
                    if ($primaryProvider === 'smart_routing') {
                        $provider = DB::table('transfer_providers')
                            ->where('is_locked', 0)
                            ->orderBy('priority')
                            ->first();

                        if (!$provider) {
                            throw new \Exception('No transfer provider available');
                        }

                        $primaryProvider = $provider->slug;
                    }

                    // Use the new BankingService with Smart Failover
                    $bankingService = new \App\Services\Banking\BankingService();
                    $result = $bankingService->verifyAccount($accountNumber, $bankCode);

                    return response()->json($result);

                } catch (\Exception $e) {
                    Log::error('Account Verification Error: ' . $e->getMessage());

                    $msg = $e->getMessage();

                    // Sanitize HTML/System Errors
                    if (str_contains($msg, '<!DOCTYPE') || str_contains($msg, '<html') || str_contains($msg, 'cURL error')) {
                        $userMessage = "Temporary Service Error. Please try again later.";
                    } elseif (str_contains($msg, 'Selected bank does not exist')) {
                        $userMessage = "The selected bank does not appear to match this account number.";
                    } elseif (str_contains($msg, 'resolve host')) {
                        $userMessage = "Network Connection Error. Please verify your internet.";
                    } else {
                        // Strip any potential HTML tags just in case
                        $userMessage = strip_tags($msg);
                        // Limit length
                        if (strlen($userMessage) > 100) {
                            $userMessage = "Verification failed. Please check account details.";
                        }
                    }

                    return response()->json([
                        'status' => 'fail',
                        'message' => $userMessage
                    ], 200); // Return 200 so app handles it as a "soft" failure (showing red text) instead of crashing
                }
            } else {
                return response()->json(['status' => 'fail', 'message' => 'Authentication required'], 403);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'], 403);
        }
    }

    /**
     * Verify account using Xixapay API
     */
    private function verifyWithXixapay($bankCode, $accountNumber)
    {
        try {
            $apiKey = '5e1a59b5fd64b39065a83ba858c9f3dc00bbaf88';
            $secretKey = '3d47f078e1dc246f65a200104b9cefeae5caf0719b6614cfa072aec60835bfea6f450e1c1568bbbdd2a4b804bf2ac437e9abe7dea8b402c4af9be3ba';

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post('https://api.xixapay.com/api/verify/bank', [
                    'businessId' => 'beaa4543320851673e7d4e3fcb05b34d329535ed', // Added Business ID
                    'bank' => $bankCode,
                    'accountNumber' => $accountNumber
                ]);

            if (!$response->successful()) {
                throw new \Exception('Xixapay API error: ' . $response->body());
            }

            $data = $response->json();

            // Xixapay response format (Based on Documentation)
            // { "status": "success", "AccountName": "JOHN DOE", "BankName": "Guaranty Trust Bank" }
            if (isset($data['AccountName'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'account_name' => $data['AccountName'],
                        'account_number' => $accountNumber,
                        'bank_code' => $bankCode
                    ]
                ]);
            }

            // Fallback or recursive check if inside data wrapper
            if (isset($data['data']['account_name'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'account_name' => $data['data']['account_name'],
                        'account_number' => $accountNumber,
                        'bank_code' => $bankCode
                    ]
                ]);
            }

            throw new \Exception('Invalid response from Xixapay: ' . json_encode($data));

        } catch (\Exception $e) {
            Log::error('Xixapay Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify account using Paystack API
     */
    private function verifyWithPaystack($bankCode, $accountNumber)
    {
        try {
            $paystackKey = DB::table('paystack_key')->first();

            if (!$paystackKey || empty($paystackKey->live)) {
                throw new \Exception('Paystack API key not configured');
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $paystackKey->live,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode
                ]);

            if (!$response->successful()) {
                throw new \Exception('Paystack API error: ' . $response->body());
            }

            $data = $response->json();

            // Paystack response format
            if ($data['status'] && isset($data['data']['account_name'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'account_name' => $data['data']['account_name'],
                        'account_number' => $data['data']['account_number'],
                        'bank_code' => $bankCode
                    ]
                ]);
            }

            throw new \Exception('Invalid response from Paystack');

        } catch (\Exception $e) {
            Log::error('Paystack Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify account using Monnify API
     */
    private function verifyWithMonnify($bankCode, $accountNumber)
    {
        try {
            // TODO: Implement Monnify account verification
            // For now, return a mock response
            return response()->json([
                'status' => 'success',
                'data' => [
                    'account_name' => 'MONNIFY TEST ACCOUNT',
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Monnify Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
