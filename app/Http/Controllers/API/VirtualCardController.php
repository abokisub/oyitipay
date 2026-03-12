<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Banking\Providers\XixapayProvider;

class VirtualCardController extends Controller
{
    protected $provider;

    public function __construct()
    {
        $this->provider = new XixapayProvider();
    }

    /**
     * Create NGN Virtual Card
     */
    public function createNgnCard(Request $request)
    {
        return $this->processCardCreation($request, 'NGN', 'card_ngn');
    }

    /**
     * Create USD Virtual Card
     */
    public function createUsdCard(Request $request)
    {
        return $this->processCardCreation($request, 'USD', 'card_usd');
    }

    /**
     * Shared Card Creation Logic
     */
    private function processCardCreation(Request $request, $currency, $lockFeature)
    {
        set_time_limit(180);
        $user = DB::table('user')->where('id', $request->user()->id ?? 0)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        // Check Global Lock
        $sysSettings = DB::table('settings')->where('id', 1)->first();
        if ($currency === 'NGN' && ($sysSettings->card_ngn_lock ?? 0) == 1) {
            return response()->json(['status' => 'error', 'message' => 'Naira Virtual Card service is currently unavailable.'], 403);
        }
        if ($currency === 'USD' && ($sysSettings->card_usd_lock ?? 0) == 1) {
            return response()->json(['status' => 'error', 'message' => 'Dollar Virtual Card service is currently unavailable.'], 403);
        }

        // 1. Check if Customer Exists
        if (empty($user->customer_id)) {
            return response()->json(['status' => 'error', 'message' => 'Please complete Customer Creation first'], 400);
        }

        // 2. Check Local Card Limit
        $existingCard = DB::table('virtual_cards')
            ->where('user_id', $user->id)
            ->where('card_type', $currency)
            ->where('status', '!=', 'terminated')
            ->first();

        if ($existingCard) {
            return response()->json([
                'status' => 'error',
                'message' => "You already have an active $currency card. Please fund it instead."
            ], 400);
        }

        // 3. Validation
        $minAmount = ($currency === 'USD') ? 3 : 0;
        $validator = Validator::make($request->all(), [
            'amount' => "required|numeric|min:$minAmount",
            'pin' => "required|numeric|digits:4",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 3a. PIN Verification
        if ($request->pin != $user->pin) {
            \Log::warning("Card Creation PIN mismatch for {$user->username}");
            return response()->json(['status' => 'error', 'message' => 'Invalid Transaction PIN'], 403);
        }

        // 4. Calculate Total Cost (Fees + FX)
        $settings = DB::table('card_settings')->where('id', 1)->first();
        $ngnRate = $settings->ngn_rate ?? 1600;

        $creationFee = 0;
        $totalDebit = 0;
        $fundingFee = 0;

        if ($currency === 'NGN') {
            $creationFee = $settings->ngn_creation_fee ?? 500;
            $fundPercent = $settings->ngn_funding_fee_percent ?? 2; // Default 2%
            $fundingFee = $request->amount * ($fundPercent / 100);

            // Amount is in NGN
            $totalDebit = $request->amount + $creationFee + $fundingFee;
        } else {
            // USD
            $creationFeeUsd = $settings->usd_creation_fee ?? 3;
            $fundPercent = $settings->usd_funding_fee_percent ?? 2; // Default 2%
            $fundingFeeUsd = $request->amount * ($fundPercent / 100);

            // Total USD needed = Amount + Creation Fee + Funding Fee
            $totalUsd = $request->amount + $creationFeeUsd + $fundingFeeUsd;
            // Convert to NGN
            $totalDebit = $totalUsd * $ngnRate;
        }

        // 5. Debit Funding Wallet
        if ($user->bal < $totalDebit) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Funds. Required: NGN ' . number_format($totalDebit, 2)], 400);
        }

        // 6. Call API
        try {
            return DB::transaction(function () use ($user, $currency, $request, $totalDebit) {
                try {
                    // Debit User
                    DB::table('user')->where('id', $user->id)->decrement('bal', $totalDebit);

                    // Create Card
                    $result = $this->provider->createVirtualCard($user->customer_id, $currency, $request->amount);

                    if ($result['status'] === 'success') {
                        // 7. Save Card Locally
                        DB::table('virtual_cards')->insert([
                            'user_id' => $user->id,
                            'provider' => 'xixapay',
                            'card_id' => $result['card_id'],
                            'card_type' => $currency,
                            'status' => 'active',
                            'full_response_json' => json_encode($result['full_response']),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        // Log Transaction (Debit)
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'amount' => $totalDebit,
                            'message' => "Created $currency Virtual Card | Fees Included",
                            'oldbal' => $user->bal, // user->bal at start of request
                            'newbal' => $user->bal - $totalDebit,
                            'habukhan_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $this->purchase_ref("CARD_CREATE_{$currency}_"),
                            'role' => 'card_creation'
                        ]);

                        return response()->json([
                            'status' => 'success',
                            'message' => "$currency Card Created Successfully",
                            'data' => $result['data']
                        ]);
                    } else {
                        throw new \Exception($result['message'] ?? 'Card creation failed');
                    }

                } catch (\Exception $e) {
                    \Log::error("Card Creation Error for {$user->username}: " . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Fund Virtual Card
     */
    public function fundCard(Request $request, $id)
    {
        set_time_limit(180);
        $user = DB::table('user')->where('id', $request->user()->id)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        // 1. Validate Ownership and Status
        $card = DB::table('virtual_cards')->where('user_id', $user->id)->where('card_id', $id)->first();
        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Card not found or access denied'], 404);
        }

        if ($card->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Card is not active'], 400);
        }

        // Check Global Lock
        $sysSettings = DB::table('settings')->where('id', 1)->first();
        if ($card->card_type === 'NGN' && ($sysSettings->card_ngn_lock ?? 0) == 1) {
            return response()->json(['status' => 'error', 'message' => 'Naira Virtual Card funding is currently unavailable.'], 403);
        }
        if ($card->card_type === 'USD' && ($sysSettings->card_usd_lock ?? 0) == 1) {
            return response()->json(['status' => 'error', 'message' => 'Dollar Virtual Card funding is currently unavailable.'], 403);
        }

        // 2. Validate Amount
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 2b. PIN Verification
        if ($request->pin != $user->pin) {
            \Log::warning("Card Funding PIN mismatch for {$user->username}");
            return response()->json(['status' => 'error', 'message' => 'Invalid Transaction PIN'], 403);
        }

        // 3. Calculate Cost + Fees
        $settings = DB::table('card_settings')->where('id', 1)->first();
        $ngnRate = $settings->ngn_rate ?? 1600;

        $amountToFund = $request->amount; // Card Currency Amount
        $baseCostNgn = 0;
        $fundFeePercent = 0;

        if ($card->card_type === 'USD') {
            // Convert USD Amount to NGN
            $baseCostNgn = $amountToFund * $ngnRate;
            $fundFeePercent = $settings->usd_funding_fee_percent ?? 2;
        } else {
            // NGN Amount
            $baseCostNgn = $amountToFund;
            $fundFeePercent = $settings->ngn_funding_fee_percent ?? 2;
        }

        // Apply Funding Fee (on the base cost in NGN)
        $feeNgn = $baseCostNgn * ($fundFeePercent / 100);
        $totalDebit = $baseCostNgn + $feeNgn;

        // 4. Debit Wallet
        if ($user->bal < $totalDebit) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient Wallet Balance. Required: NGN ' . number_format($totalDebit, 2)], 400);
        }

        try {
            return DB::transaction(function () use ($user, $card, $totalDebit, $amountToFund) {
                try {
                    DB::table('user')->where('id', $user->id)->decrement('bal', $totalDebit);

                    // 5. Call API
                    $result = $this->provider->fundVirtualCard($card->card_id, $amountToFund);

                    if ($result['status'] === 'success') {
                        // Log Success
                        DB::table('message')->insert([
                            'username' => $user->username,
                            'amount' => $totalDebit,
                            'message' => "Funded {$card->card_type} Virtual Card | Fees Included",
                            'oldbal' => $user->bal,
                            'newbal' => $user->bal - $totalDebit,
                            'habukhan_date' => $this->system_date(),
                            'plan_status' => 1,
                            'transid' => $this->purchase_ref("CARD_FUND_"),
                            'role' => 'card_funding'
                        ]);

                        return response()->json(['status' => 'success', 'message' => 'Card Funded Successfully', 'data' => $result]);
                    } else {
                        throw new \Exception($result['message'] ?? 'Card funding failed');
                    }
                } catch (\Exception $e) {
                    \Log::error("Card Funding API Error for {$user->username}: " . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Withdraw from Virtual Card
     */
    public function withdrawCard(Request $request, $id)
    {
        $user = DB::table('user')->where('id', $request->user()->id)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        $card = DB::table('virtual_cards')->where('user_id', $user->id)->where('card_id', $id)->first();
        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|numeric|digits:4',
        ]);

        if ($validator->fails())
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);

        // 1b. PIN Verification
        if ($request->pin != $user->pin) {
            \Log::warning("Card Withdrawal PIN mismatch for {$user->username}");
            return response()->json(['status' => 'error', 'message' => 'Invalid Transaction PIN'], 403);
        }

        try {
            // 1. Call API (Withdraw First)
            $result = $this->provider->withdrawVirtualCard($card->card_id, $request->amount);

            if ($result['status'] === 'success') {
                // 2. Credit Wallet
                // Convert back if USD
                $amountToCredit = $request->amount;
                if ($card->card_type === 'USD') {
                    $rate = 1500; // Buyback Rate
                    $amountToCredit = $request->amount * $rate;
                }

                DB::table('user')->where('id', $user->id)->increment('bal', $amountToCredit);

                // Log Success
                DB::table('message')->insert([
                    'username' => $user->username,
                    'amount' => $amountToCredit,
                    'message' => "Withdrawal from {$card->card_type} Virtual Card",
                    'oldbal' => $user->bal - $amountToCredit,
                    'newbal' => $user->bal,
                    'habukhan_date' => $this->system_date(),
                    'plan_status' => 1,
                    'transid' => $this->purchase_ref("CARD_WITHDRAW_"),
                    'role' => 'card_withdrawal'
                ]);

                return response()->json(['status' => 'success', 'message' => 'Withdrawal Successful', 'data' => $result]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Service Error'], 500);
        }
    }

    /**
     * Change Status (Freeze/Unfreeze)
     */
    /**
     * Change Status (Freeze/Unfreeze/Block)
     * PUT /api/user/card/{id}/status
     */
    public function changeStatus(Request $request, $id)
    {
        $user = DB::table('user')->where('id', $request->user()->id)->first();
        $card = DB::table('virtual_cards')->where('user_id', $user->id)->where('card_id', $id)->first();
        if (!$card)
            return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,frozen,blocked',
        ]);
        if ($validator->fails())
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);

        try {
            $result = $this->provider->changeCardStatus($card->card_id, $request->status);

            if ($result['status'] === 'success') {
                // Update Local Status
                DB::table('virtual_cards')->where('id', $card->id)->update([
                    'status' => $request->status,
                    'updated_at' => now()
                ]);

                // Log Status Change
                DB::table('message')->insert([
                    'username' => $user->username,
                    'amount' => 0,
                    'message' => "Card Status Changed to " . ucfirst($request->status) . " | {$card->card_type} Card",
                    'oldbal' => $user->bal,
                    'newbal' => $user->bal,
                    'habukhan_date' => $this->system_date(),
                    'plan_status' => 1,
                    'transid' => $this->purchase_ref("CARD_STATUS_"),
                    'role' => 'card_status_change'
                ]);

                return response()->json(['status' => 'success', 'message' => $result['message']]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Service Error'], 500);
        }
    }

    /**
     * Get User Cards
     */
    public function getCards(Request $request)
    {
        $cards = DB::table('virtual_cards')->where('user_id', $request->user()->id)->get();
        return response()->json(['status' => 'success', 'data' => $cards]);
    }

    /**
     * Get Card Transactions (Phase 7)
     */
    public function getCardTransactions(Request $request, $id)
    {
        $user = DB::table('user')->where('id', $request->user()->id)->first();
        // Verify Ownership
        $card = DB::table('virtual_cards')->where('user_id', $user->id)->where('card_id', $id)->first();

        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);
        }

        $transactions = DB::table('card_transactions')
            ->where('card_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $transactions]);
    }
    /**
     * Get Card Details & Balance
     * GET /api/user/card/{id}/details
     */
    public function getCardDetails(Request $request, $id)
    {
        $user = DB::table('user')->where('id', $request->user()->id)->first();

        // 1. Verify Ownership
        $card = DB::table('virtual_cards')->where('user_id', $user->id)->where('card_id', $id)->first();
        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Card not found or access denied'], 404);
        }

        try {
            // 2. Call Provider
            $result = $this->provider->getCardDetails($card->card_id);

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Card details retrieved',
                    'data' => $result['data']
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);

        } catch (\Exception $e) {
            \Log::error("Get Card Details Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }
}
