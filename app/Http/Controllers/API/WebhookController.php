<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ReceiptService;

class WebhookController extends Controller
{
    public function Simserver(Request $request)
    {
        if ($request->status and $request->user_reference and $request->true_response) {
            if (DB::table('data')->where(['transid' => $request->status])->count() == 1) {
                $trans = DB::table('data')->where(['transid' => $request->user_reference])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();
                if ($request->status == 'Done') {
                    $status = 'success';
                    $receiptService = new ReceiptService();
                    $successMessage = $receiptService->getFullMessage('DATA', [
                        'plan' => $trans->plan_name,
                        'recipient' => $trans->plan_phone,
                        'reference' => $trans->transid,
                        'status' => 'SUCCESS',
                        'provider' => $trans->network
                    ]);
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $request->true_response]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                    (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'success', $trans->transid);
                }
                else {
                    if ($trans->plan_status !== 2) {

                        if (strtolower($trans->wallet) == 'wallet') {
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                            $user_balance = $user->bal;
                        }
                        else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }



                        $status = "fail";
                        $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $trans->network . " " . $trans->plan_name . " for " . $trans->plan_phone . " but the transaction failed. Your wallet has been refunded.";
                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $request->true_response, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $failMessage, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'failed', $trans->transid);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $request->true_response])); //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        }
        else {
            return ['status' => 'fail'];
        }
    }
    public function HabukhanWebhook()
    {
        $response = json_decode(file_get_contents("php://input"), true);
        if ((isset($response['status'])) and (isset($response['request-id'])) and isset($response['response'])) {

            if (DB::table('data')->where(['transid' => $response['request-id']])->count() == 1) {
                $trans = DB::table('data')->where(['transid' => $response['request-id']])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();

                if ($response['status'] == 'success') {
                    $status = "success";
                    $receiptService = new ReceiptService();
                    $successMessage = $receiptService->getFullMessage('DATA', [
                        'plan' => $trans->plan_name,
                        'recipient' => $trans->plan_phone,
                        'reference' => $trans->transid,
                        'status' => 'SUCCESS',
                        'provider' => $trans->network
                    ]);
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $response['response']]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                    (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'success', $trans->transid);
                }
                else {
                    if ($trans->plan_status !== 2) {
                        $status = "fail";

                        if (strtolower($trans->wallet) == 'wallet') {
                            $user_balance = $user->bal;
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                        }
                        else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }


                        $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $trans->network . " " . $trans->plan_name . " for " . $trans->plan_phone . " but the transaction failed. Your wallet has been refunded.";

                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $response['response'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $failMessage, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'failed', $trans->transid);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $response['response']])); //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        }
    }

    public function MegasubWebhook()
    {
        $response = json_decode(file_get_contents("php://input"), true);
        if ($response['status'] and $response['id'] and $response['msg']) {
            if (
            DB::table('data')->where(['mega_trans' => $response['id']])->where(function ($query) {
                $query->where('plan_status', 1)->orwhere('plan_status', 0);
            })->count() == 1
            ) {
                $trans = DB::table('data')->where(['mega_trans' => $response['id']])->first();
                $user = DB::table('user')->where(['username' => $trans->username, 'status' => 1])->first();
                if ($response['status'] == 'success') {
                    $status = "success";
                    $receiptService = new ReceiptService();
                    $successMessage = $receiptService->getFullMessage('DATA', [
                        'plan' => $trans->plan_name,
                        'recipient' => $trans->plan_phone,
                        'reference' => $trans->transid,
                        'status' => 'SUCCESS',
                        'provider' => $trans->network
                    ]);
                    DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'api_response' => $response['msg']]);
                    DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                    (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'success', $trans->transid);
                }
                else {
                    if ($trans->plan_status !== 2) {
                        if (strtolower($trans->wallet) == 'wallet') {
                            DB::table('user')->where('username', $trans->username)->update(['bal' => $user->bal + $trans->amount]);
                            $user_balance = $user->bal;
                        }
                        else {
                            $wallet_bal = strtolower($trans->wallet) . "_bal";
                            $b = DB::table('wallet_funding')->where(['username' => $trans->username])->first();
                            $user_balance = $b->$wallet_bal;
                            DB::table('wallet_funding')->where('username', $trans->username)->update([$wallet_bal => $user_balance + $trans->amount]);
                        }
                        $status = "fail";
                        $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $trans->network . " " . $trans->plan_name . " for " . $trans->plan_phone . " but the transaction failed. Your wallet has been refunded.";
                        DB::table('data')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'api_response' => $response['msg'], 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        DB::table('message')->where(['transid' => $trans->transid])->update(['plan_status' => 2, 'message' => $failMessage, 'oldbal' => $user_balance, 'newbal' => $user_balance + $trans->amount]);
                        (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Service Purchase', $trans->amount, 'failed', $trans->transid);
                    }
                }
                if ($status) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $user->webhook);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => $status, 'request-id' => $trans->transid, 'response' => $response['msg']])); //Post Fields
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        }
    }

    public function AutopilotWebhook(Request $request)
    {
        $payload = $request->all();
        \Log::info('Autopilot Webhook received:', $payload);

        // Check if payload has the expected structure
        if (isset($payload['status']) && isset($payload['data']['yourReference'])) {
            $reference = $payload['data']['yourReference'];
            $status = $payload['status'] == true ? 'success' : 'fail';

            $tables = ['data', 'airtime', 'cable', 'cash'];
            foreach ($tables as $table) {
                // Autopilot transactions store the generated reference in `api_reference`
                $transaction = DB::table($table)->where('api_reference', $reference)->first();
                if ($transaction) {
                    // Check idempotency
                    if ($transaction->plan_status == 1 || $transaction->plan_status == 2) {
                        return response()->json(['status' => 'success', 'message' => 'Already processed']);
                    }

                    if ($status == 'success') {
                        DB::table($table)->where('api_reference', $reference)->update(['plan_status' => 1]);
                        DB::table('message')->where('transid', $transaction->transid)->update(['plan_status' => 1]);

                        // Specific logic for Airtime to Cash (Auto-crediting)
                        if ($table == 'cash') {
                            $user = DB::table('user')->where('username', $transaction->username)->first();
                            if ($user) {
                                // If payment type is wallet, credit the user
                                if (strtolower($transaction->payment_type) == 'wallet') {
                                    DB::table('user')->where('username', $user->username)->increment('bal', $transaction->amount_credit);
                                    
                                    // Update history messages with new balance
                                    $newBal = $user->bal + $transaction->amount_credit;
                                    DB::table('message')->where('transid', $transaction->transid)->update([
                                        'message' => 'Airtime 2 Cash Success (Auto)',
                                        'oldbal' => $user->bal,
                                        'newbal' => $newBal
                                    ]);
                                    DB::table('cash')->where('transid', $transaction->transid)->update([
                                        'oldbal' => $user->bal,
                                        'newbal' => $newBal
                                    ]);

                                    // Push notification
                                    try {
                                        (new \App\Services\FirebaseService())->sendNotification(
                                            $user->app_token,
                                            "Airtime to Cash Approved",
                                            "Your airtime conversion was successful. ₦" . number_format($transaction->amount_credit, 2) . " credited to your wallet.",
                                            ['type' => 'transaction', 'action' => 'airtime_cash']
                                        );
                                    } catch (\Exception $e) {
                                        \Log::warning('AirtimeCash Push failed: ' . $e->getMessage());
                                    }
                                } else {
                                    DB::table('message')->where('transid', $transaction->transid)->update(['message' => 'Airtime 2 Cash Success (Auto Bank/Other)']);
                                }
                            }
                        } else {
                            // Send push notification for Data/Airtime success if needed
                            $user = DB::table('user')->where('username', $transaction->username)->first();
                            if ($user) {
                                (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, ucfirst($table) . ' Purchase', $transaction->amount, 'success', $transaction->transid);
                            }
                        }

                    } elseif ($status == 'fail') {
                        DB::table($table)->where('api_reference', $reference)->update(['plan_status' => 2]);
                        DB::table('message')->where('transid', $transaction->transid)->update(['plan_status' => 2]);

                        // Handle Refunds for Purchases (Data, Airtime, Cable)
                        if ($table != 'cash') {
                            $user = DB::table('user')->where('username', $transaction->username)->first();
                            if ($user) {
                                $refund_amount = $transaction->amount ?? 0;
                                if ($table == 'airtime') $refund_amount = $transaction->discount ?? $transaction->amount;
                                elseif ($table == 'cable') $refund_amount = $transaction->amount + ($transaction->charges ?? 0);

                                if (strtolower($transaction->wallet ?? 'wallet') == 'wallet') {
                                    DB::table('user')->where('username', $user->username)->increment('bal', $refund_amount);
                                    $newBal = $user->bal + $refund_amount;
                                } else {
                                    $wallet_bal = strtolower($transaction->wallet) . "_bal";
                                    DB::table('wallet_funding')->where('username', $user->username)->increment($wallet_bal, $refund_amount);
                                    $newBal = DB::table('wallet_funding')->where('username', $user->username)->value($wallet_bal);
                                }

                                DB::table('message')->where('transid', $transaction->transid)->update([
                                    'message' => "Transaction Failed (Refund) - Webhook",
                                    'oldbal' => $user->bal,
                                    'newbal' => $newBal
                                ]);
                                
                                (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, ucfirst($table) . ' Purchase', $transaction->amount, 'failed', $transaction->transid);
                            }
                        } else {
                            // If Airtime to Cash failed
                            DB::table('message')->where('transid', $transaction->transid)->update(['message' => 'Airtime 2 Cash Failed (Auto)']);
                            $user = DB::table('user')->where('username', $transaction->username)->first();
                            if ($user && $user->app_token) {
                                try {
                                    (new \App\Services\FirebaseService())->sendNotification(
                                        $user->app_token,
                                        "Airtime to Cash Declined",
                                        "Your airtime conversion request has failed.",
                                        ['type' => 'transaction', 'action' => 'airtime_cash']
                                    );
                                } catch (\Exception $e) {
                                    \Log::warning('AirtimeCash Push failed: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Trigger user's own webhook if they have one configured
                    $user = DB::table('user')->where('username', $transaction->username)->first();
                    if ($user && !empty($user->webhook)) {
                        @$ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $user->webhook);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'status' => $status, 
                            'request-id' => $transaction->transid, 
                            'response' => $payload['data']['message'] ?? 'Webhook Update'
                        ]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_exec($ch);
                        curl_close($ch);
                    }

                    return response()->json(['status' => 'success'], 200);
                }
            }
        }

        return response()->json(['status' => 'ignored'], 200);
    }

    /**
     * Paystack Dedicated Virtual Account Webhook
     * Handles automatic wallet funding when users transfer to their Wema Bank account
     */
    public function paystackDedicatedAccountWebhook(Request $request)
    {
        try {
            // 1. Get raw payload and signature
            $rawPayload = $request->getContent();
            $payload = json_decode($rawPayload, true);
            $signature = $request->header('x-paystack-signature');
            
            \Log::info("💰 Paystack DVA Webhook Received", [
                'event' => $payload['event'] ?? 'unknown',
                'reference' => $payload['data']['reference'] ?? 'none'
            ]);

            // 2. Verify signature
            $secretKey = null;
            $paystackKey = DB::table('paystack_key')->first();
            
            if ($paystackKey && !empty($paystackKey->live) && $paystackKey->live !== 'sk_test_placeholder') {
                $secretKey = $paystackKey->live;
            } else {
                $habukhanKey = DB::table('habukhan_key')->first();
                $secretKey = $habukhanKey->psk ?? config('app.paystack_secret_key');
            }

            if ($signature && $secretKey) {
                $calculatedSignature = hash_hmac('sha512', $rawPayload, $secretKey);
                if (!hash_equals($calculatedSignature, $signature)) {
                    \Log::warning("💰 Paystack DVA: Invalid signature mismatch");
                    return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
                }
            } elseif (!$signature) {
                \Log::warning("💰 Paystack DVA: Missing signature header");
            }

            $event = $payload['event'] ?? '';

            // 3. Handle charge.success event
            if ($event === 'charge.success') {
                $data = $payload['data'] ?? [];
                
                // Flexible channel check
                $channel = $data['channel'] ?? '';
                if ($channel !== 'dedicated_nuban' && $channel !== 'bank_transfer') {
                    \Log::info("💰 Paystack: Ignoring channel: $channel");
                    return response()->json(['status' => 'ignored']);
                }

                // 4. Extract Account Number (check multiple locations)
                $accountNumber = $data['dedicated_account']['account_number'] ?? 
                                 $data['authorization']['receiver_bank_account_number'] ?? 
                                 null;
                
                $amount = ($data['amount'] ?? 0) / 100; // Convert from kobo
                $reference = $data['reference'] ?? null;

                if (!$accountNumber || !$amount || !$reference) {
                    \Log::warning("💰 Paystack DVA: Missing required fields", [
                        'account' => $accountNumber ? 'present' : 'missing',
                        'amount' => $amount,
                        'ref' => $reference
                    ]);
                    return response()->json(['status' => 'error', 'message' => 'Missing fields'], 400);
                }

                // 5. Find User (Check main table, then fallback to user_bank)
                $accountNumber = trim($accountNumber);
                $user = DB::table('user')->where('paystack_account', $accountNumber)->first();

                if (!$user) {
                    \Log::info("💰 Paystack DVA: Account $accountNumber not in user table, checking user_bank...");
                    $userBank = DB::table('user_bank')->where('account_number', $accountNumber)->first();
                    if ($userBank && !empty($userBank->username)) {
                        $user = DB::table('user')->where('username', $userBank->username)->first();
                    }
                }

                if (!$user) {
                    \Log::warning("💰 Paystack DVA: User not found for account $accountNumber in any table");
                    return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
                }

                // 6. Idempotency Check
                $existingDeposit = DB::table('deposit')->where('monify_ref', $reference)->first();
                if ($existingDeposit) {
                    \Log::info("💰 Paystack DVA: Duplicate transaction $reference");
                    return response()->json(['status' => 'success', 'message' => 'Already processed']);
                }

                // 7. Calculate Fees & Net Amount
                $settings = DB::table('settings')->first();
                $paystackCharge = $settings->paystack_charge ?? 0;
                $netAmount = max(0, $amount - $paystackCharge);

                // 8. Process Transaction
                DB::transaction(function () use ($user, $amount, $netAmount, $reference, $paystackCharge) {
                    $oldBalance = $user->bal;
                    $newBalance = $oldBalance + $netAmount;

                    // Update Balance
                    DB::table('user')->where('id', $user->id)->update(['bal' => $newBalance]);

                    // Record Deposit
                    DB::table('deposit')->insert([
                        'username' => $user->username,
                        'amount' => $amount,
                        'oldbal' => $oldBalance,
                        'newbal' => $newBalance,
                        'wallet_type' => 'User Wallet',
                        'type' => 'Paystack Funding',
                        'credit_by' => 'Paystack',
                        'date' => now(),
                        'status' => 1,
                        'transid' => $reference,
                        'charges' => $paystackCharge,
                        'monify_ref' => $reference
                    ]);

                    // Record History
                    $historyMsg = "✅ Wallet Funded via Paystack\n"
                                . "Amount: ₦" . number_format($amount, 2) . "\n"
                                . ($paystackCharge > 0 ? "Charge: ₦" . number_format($paystackCharge, 2) . "\n" : "")
                                . "Net Credit: ₦" . number_format($netAmount, 2) . "\n"
                                . "Ref: $reference";

                    DB::table('message')->insert([
                        'username' => $user->username,
                        'amount' => $netAmount,
                        'message' => $historyMsg,
                        'oldbal' => $oldBalance,
                        'newbal' => $newBalance,
                        'transid' => $reference,
                        'habukhan_date' => now()->toDateTimeString(),
                        'plan_status' => 1,
                        'phone_account' => 'Paystack Funding',
                        'role' => 'credit'
                    ]);

                    // Send Notification
                    try {
                        (new \App\Services\NotificationService())->sendWalletCreditNotification(
                            $user, 
                            $netAmount, 
                            'Paystack', 
                            $reference
                        );
                    } catch (\Exception $e) {
                        \Log::error("💰 Paystack DVA: Notification failed: " . $e->getMessage());
                    }

                    \Log::info("💰 Paystack DVA: Success - Credited ₦$netAmount to {$user->username}");
                });

                return response()->json(['status' => 'success', 'message' => 'Wallet credited']);
            }

            return response()->json(['status' => 'ignored', 'message' => 'Event not handled']);

        } catch (\Exception $e) {
            \Log::error("💰 Paystack DVA Webhook Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Unified Bank Transfer Webhook Handler (Paystack / Xixapay)
     * Single Source of Truth for Transfer Status.
     */
    public function transferWebhook(Request $request, $provider)
    {
        // 1. Normalize Payload
        $payload = $request->all();
        \Log::info("📞 Transfer Webhook Received ($provider):", $payload);

        $status = null; // 'SUCCESS', 'FAILED'
        $reference = null;
        $message = 'Webhook processed';

        try {
            if ($provider == 'paystack') {
                // Verify Signature (Important!)
                // $signature = $request->header('x-paystack-signature');
                // if ($signature !== hash_hmac('sha512', file_get_contents("php://input"), config('paystack.secret_key'))) { ... }

                $event = $payload['event'] ?? '';
                if ($event == 'transfer.success') {
                    $status = 'SUCCESS';
                    $reference = $payload['data']['reference'];
                }
                elseif ($event == 'transfer.failed' || $event == 'transfer.reversed') {
                    $status = 'FAILED';
                    $reference = $payload['data']['reference'];
                    $message = $payload['data']['reason'] ?? 'Transfer Failed';
                }

            }
            elseif ($provider == 'xixapay') {
                // Xixapay Structure (Assumed based on pattern)
                $reference = $payload['reference'] ?? $payload['data']['reference'] ?? null;
                $rawStatus = strtolower($payload['status'] ?? $payload['data']['status'] ?? '');

                if ($rawStatus == 'success' || $rawStatus == 'successful') {
                    $status = 'SUCCESS';
                }
                elseif ($rawStatus == 'failed' || $rawStatus == 'reversed') {
                    $status = 'FAILED';
                    $message = $payload['message'] ?? 'Transfer Failed';
                }
            }

            if (!$status || !$reference) {
                return response()->json(['status' => 'ignored', 'message' => 'Not a relevant transfer event']);
            }

            // 2. Locate Transaction
            $transfer = DB::table('transfers')->where('reference', $reference)->first();

            if (!$transfer) {
                \Log::warning("📞 Webhook: Transfer reference not found: $reference");
                return response()->json(['status' => 'fail', 'message' => 'Ref not found']);
            }

            // 3. Idempotency & State Machine Check
            if ($transfer->status == 'SUCCESS' || $transfer->status == 'FAILED') {
                \Log::info("📞 Webhook: Transaction $reference already final (" . $transfer->status . "). Ignoring.");
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // 4. Update State (Atomic)
            DB::transaction(function () use ($transfer, $status, $message, $reference) {
                if ($status == 'SUCCESS') {
                    // Update Transfer
                    DB::table('transfers')->where('id', $transfer->id)->update([
                        'status' => 'SUCCESS',
                        'updated_at' => now()
                    ]);

                    $receiptService = new ReceiptService();
                    $successMessage = $receiptService->getFullMessage('BANK_TRANSFER', [
                        'amount' => $transfer->amount,
                        'account_name' => $transfer->account_name,
                        'account_number' => $transfer->account_number,
                        'bank_name' => $transfer->bank_name,
                        'reference' => $reference,
                        'status' => 'SUCCESS'
                    ]);

                    // Update Message (History)
                    DB::table('message')->where('transid', $reference)->update([
                        'plan_status' => 1,
                        'message' => $successMessage
                    ]);
                    (new \App\Services\NotificationService())->sendServicePurchaseNotification(DB::table('user')->where('id', $transfer->user_id)->first(), 'Bank Transfer', $transfer->amount, 'success', $reference);

                }
                elseif ($status == 'FAILED') {
                    // REFUND USER
                    $user = DB::table('user')->where('id', $transfer->user_id)->lockForUpdate()->first();
                    $refund_bal = $user->bal + $transfer->amount + $transfer->charge;

                    DB::table('user')->where('id', $user->id)->update(['bal' => $refund_bal]);

                    DB::table('transfers')->where('id', $transfer->id)->update([
                        'status' => 'FAILED',
                        'updated_at' => now()
                    ]);

                    $failMessage = "❌ Bank Transfer Failed\n\nYou attempted to transfer ₦" . $transfer->amount . " to " . $transfer->account_name . " (" . $transfer->account_number . " / " . $transfer->bank_name . ") but the transaction failed. Funds refunded.";

                    DB::table('message')->where('transid', $reference)->update([
                        'plan_status' => 2,
                        'message' => $failMessage,
                        'newbal' => $refund_bal
                    ]);
                    (new \App\Services\NotificationService())->sendServicePurchaseNotification($user, 'Bank Transfer', $transfer->amount, 'failed', $reference);
                    (new \App\Services\NotificationService())->sendWalletCreditNotification($user, $transfer->amount + $transfer->charge, 'Transfer Refund', $reference);
                }
            });

            return response()->json(['status' => 'success']);

        }
        catch (\Exception $e) {
            \Log::error("❌ Webhook Error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Phase 6: Xixapay Card Webhook
     * Handles Transaction Events & Status Changes (Terminated, Frozen)
     */
    public function handleCardWebhook(Request $request)
    {
        // 1. Get Raw Content & Header
        $payload = file_get_contents("php://input");
        $signatureHeader = $request->header('xixapay');
        $secretKey = config('services.xixapay.secret_key'); // Ensure this is mapped in config

        // 2. Verify Signature
        if (!$signatureHeader) {
            \Log::warning("💳 Webhook: Missing 'xixapay' signature header.");
            return response()->json(['status' => 'error', 'message' => 'Missing Signature'], 400);
        }

        // Calculate Signature
        $realSecret = str_replace('Bearer ', '', config('services.xixapay.authorization'));
        $calculatedSignature = hash_hmac('sha256', $payload, $realSecret);

        if (!hash_equals($calculatedSignature, (string)$signatureHeader)) {
            \Log::warning("💳 Webhook: Signature Mismatch. Cal: $calculatedSignature / Head: $signatureHeader");
            return response()->json(['status' => 'error', 'message' => 'Invalid Signature'], 400);
        }

        $payloadArray = json_decode($payload, true);
        \Log::info("💳 Card Webhook Verified:", $payloadArray);

        try {
            // 3. Extract Data
            $cardId = $payloadArray['card_id'] ?? null;
            $transId = $payloadArray['transaction_id'] ?? $payloadArray['id'] ?? null;
            $status = strtolower($payloadArray['status'] ?? '');

            if (!$cardId) {
                return response()->json(['status' => 'ignored', 'message' => 'No card_id']);
            }

            // 3. Find Card
            $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
            if (!$card) {
                \Log::warning("💳 Webhook: Card not found locally: $cardId");
                return response()->json(['status' => 'ignored', 'message' => 'Card not found']);
            }

            // 4. Handle Status Changes (Termination/Freeze)
            if ($status === 'terminated' || $status === 'blocked') {
                DB::table('virtual_cards')->where('id', $card->id)->update([
                    'status' => 'terminated', // Or 'blocked'
                    'updated_at' => now()
                ]);
                \Log::info("💳 Card $cardId marked as $status");
            }
            elseif ($status === 'frozen') {
                DB::table('virtual_cards')->where('id', $card->id)->update([
                    'status' => 'frozen',
                    'updated_at' => now()
                ]);
            }
            elseif ($status === 'active') { // Unfreeze
                DB::table('virtual_cards')->where('id', $card->id)->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);
            }

            // 5. Handle Transactions (Debit/Credit)
            // If it has amount and transaction_id, log it.
            if ($transId && isset($payloadArray['amount'])) {
                // Check idempotency
                $exists = DB::table('card_transactions')->where('xixapay_transaction_id', $transId)->exists();

                if (!$exists) {
                    DB::table('card_transactions')->insert([
                        'card_id' => $cardId,
                        'xixapay_transaction_id' => $transId,
                        'amount' => $payloadArray['amount'],
                        'currency' => $payloadArray['currency'] ?? 'USD', // Default assumption
                        'status' => $status, // success, failed, pending
                        'merchant_name' => $payloadArray['merchant_name'] ?? 'Unknown',
                        'raw_webhook_json' => json_encode($payloadArray),
                        'created_at' => now(), // Or payload timestamp
                        'updated_at' => now()
                    ]);
                    \Log::info("💳 Card Transaction Logged: $transId");

                    // --- FAILED TRANSACTION LOGIC ---
                    if ($status === 'failed') {
                        $settings = DB::table('card_settings')->where('id', 1)->first();
                        $ngnRate = $settings->ngn_rate ?? 1600;

                        // 1. Charge Fee
                        if ($card->card_type === 'USD') {
                            $failedFeeUsd = $settings->usd_failed_tx_fee ?? 0.4;
                            if ($failedFeeUsd > 0) {
                                $feeNgn = $failedFeeUsd * $ngnRate;
                                // Debit User Wallet
                                DB::table('user')->where('id', $card->user_id)->decrement('bal', $feeNgn);

                                // Log Fee
                                $user = DB::table('user')->where('id', $card->user_id)->first();
                                (new \App\Services\NotificationService())->sendWalletDebitNotification($user, $feeNgn, "Failed Card Transaction Fee ($failedFeeUsd USD)", 'FAIL_FEE_' . uniqid());
                                DB::table('message')->insert([
                                    'username' => $user->username ?? 'System',
                                    'amount' => $feeNgn,
                                    'message' => "Charge: Failed Card Transaction Fee ($failedFeeUsd USD)",
                                    'oldbal' => $user->bal + $feeNgn,
                                    'newbal' => $user->bal,
                                    'habukhan_date' => now(),
                                    'plan_status' => 1,
                                    'transid' => 'FAIL_FEE_' . uniqid(),
                                    'role' => 'card_fee'
                                ]);
                            }
                        }

                        // 2. Check Termination Rule (3 failures today)
                        $todayFailures = DB::table('card_transactions')
                            ->where('card_id', $cardId)
                            ->where('status', 'failed')
                            ->whereDate('created_at', now()->toDateString())
                            ->count();

                        if ($todayFailures >= 3) {
                            \Log::info("💳 Card $cardId has 3 failed transactions today. Terminating...");
                            // Terminate Card via Provider
                            try {
                                $provider = new \App\Services\Banking\Providers\XixapayProvider();
                                // Assuming terminateVirtualCard exists, or use changeCardStatus('blocked')
                                // $provider->terminateVirtualCard($cardId); 
                                // Ideally use 'blocked' first for safety
                                $provider->changeCardStatus($cardId, 'blocked');

                                DB::table('virtual_cards')->where('id', $card->id)->update([
                                    'status' => 'terminated', // Flag as terminated locally
                                    'updated_at' => now()
                                ]);
                            }
                            catch (\Exception $e) {
                                \Log::error("Failed to auto-terminate card: " . $e->getMessage());
                            }
                        }
                    }
                // --------------------------------
                }
            }

            return response()->json(['status' => 'success']);

        }
        catch (\Exception $e) {
            \Log::error("❌ Card Webhook Error: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}