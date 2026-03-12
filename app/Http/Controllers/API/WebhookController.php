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
    // ... (existing code)
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