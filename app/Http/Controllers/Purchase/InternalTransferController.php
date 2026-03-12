<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Beneficiary;
use App\Services\ReceiptService;

class InternalTransferController extends Controller
{
    /**
     * Verify a user exists by Email or Username (for transfer recipient)
     */
    public function verifyUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
        }

        $identifier = trim($request->identifier);
        $phone_variant1 = null;
        $phone_variant2 = null;

        // If numeric and length 10/11, handle variants
        if (is_numeric($identifier)) {
            if (strlen($identifier) == 10) {
                $phone_variant1 = '0' . $identifier; // 11-digit version
                $phone_variant2 = $identifier; // 10-digit version
            }
            elseif (strlen($identifier) == 11) {
                $phone_variant1 = $identifier;
                $phone_variant2 = substr($identifier, 1); // 10-digit version
            }
        }

        \Log::info('Internal Transfer: Verifying user', [
            'identifier' => $identifier,
            'phone_variant1' => $phone_variant1,
            'phone_variant2' => $phone_variant2,
        ]);

        // Search by username, email, or phone variants
        $user = DB::table('user')
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->when($phone_variant1, function ($q) use ($phone_variant1, $phone_variant2) {
            return $q->orWhere('phone', $phone_variant1)->orWhere('phone', $phone_variant2);
        })
            ->select('id', 'username', 'name', 'phone')
            ->first();

        \Log::info('Internal Transfer: User search result', [
            'found' => $user ? true : false,
            'user_id' => $user->id ?? null,
            'username' => $user->username ?? null,
            'phone' => $user->phone ?? null,
        ]);

        if ($user) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'name' => $user->name ?? $user->username,
                    'username' => $user->username,
                    'id' => $user->id
                ]
            ]);
        }
        else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Account not found. Please check the phone number, username, or email and try again.'
            ])->setStatusCode(404);
        }
    }

    /**
     * Execute Internal Wallet Transfer
     */
    public function transfer(Request $request)
    {
        $user_id = $request->header('id') ?? $request->user_id;

        // AUTHENTICATION (Consistent with other controllers)
        $verified_id = $this->verifyapptoken($user_id);
        if (!$verified_id) {
            return response()->json(['status' => 'fail', 'message' => 'Authentication Failed'])->setStatusCode(403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'recipient_identifier' => 'required|string',
            'pin' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(400);
        }

        $amount = $request->amount;
        $recipient_identifier = $request->recipient_identifier;
        $pin = $request->pin;

        // Apply Tier Limits via LimitService
        $sender_temp = DB::table('user')->where('id', $verified_id)->first();
        $limitCheck = \App\Services\LimitService::checkLimit($sender_temp, $amount);
        if (!$limitCheck['allowed']) {
            return response()->json([
                'status' => 'fail',
                'message' => $limitCheck['message']
            ])->setStatusCode(403);
        }

        try {
            // ATOMIC TRANSACTION
            $result = DB::transaction(function () use ($verified_id, $amount, $recipient_identifier, $pin, $request) {

                // 1. Lock Sender
                $sender = DB::table('user')->where('id', $verified_id)->lockForUpdate()->first();
                if (!$sender)
                    throw new \Exception("Sender not found");

                // 2. Validate PIN
                if (trim($sender->pin) != trim($pin)) {
                    throw new \Exception("Invalid Transaction PIN");
                }

                // 3. Balance Check
                if ($sender->bal < $amount) {
                    throw new \Exception("Insufficient Balance");
                }

                $recipient_variant1 = null;
                $recipient_variant2 = null;
                if (is_numeric($recipient_identifier)) {
                    if (strlen($recipient_identifier) == 10) {
                        $recipient_variant1 = '0' . $recipient_identifier;
                        $recipient_variant2 = $recipient_identifier;
                    }
                    elseif (strlen($recipient_identifier) == 11) {
                        $recipient_variant1 = $recipient_identifier;
                        $recipient_variant2 = substr($recipient_identifier, 1);
                    }
                }

                // 4. Lock Recipient
                $recipient = DB::table('user')
                    ->where('username', $recipient_identifier)
                    ->orWhere('email', $recipient_identifier)
                    ->when($recipient_variant1, function ($q) use ($recipient_variant1, $recipient_variant2) {
                    return $q->orWhere('phone', $recipient_variant1)->orWhere('phone', $recipient_variant2);
                }
                )
                    ->lockForUpdate()
                    ->first();

                if (!$recipient)
                    throw new \Exception("Recipient not found");
                if ($recipient->id == $sender->id)
                    throw new \Exception("Cannot transfer to self");

                // 5. Calculate New Balances
                $sender_new_bal = $sender->bal - $amount;
                $recipient_new_bal = $recipient->bal + $amount;

                // 6. Execute Updates
                DB::table('user')->where('id', $sender->id)->update(['bal' => $sender_new_bal]);
                DB::table('user')->where('id', $recipient->id)->update(['bal' => $recipient_new_bal]);

                // 7. Log Transactions (Message Table)
                $transid = $this->purchase_ref('INT_');
                $date = $this->system_date();

                // Sender Log
                $receiptService = new ReceiptService();
                $senderMessage = $receiptService->getFullMessage('INTERNAL_TRANSFER', [
                    'amount' => $amount,
                    'recipient_username' => $recipient->username,
                    'reference' => $transid,
                    'status' => 'SUCCESS'
                ]);

                DB::table('message')->insert([
                    'username' => $sender->username,
                    'amount' => $amount,
                    'message' => $senderMessage,
                    'oldbal' => $sender->bal,
                    'newbal' => $sender_new_bal,
                    'habukhan_date' => $date,
                    'plan_status' => 1, // Success
                    'transid' => $transid,
                    'role' => 'transfer_sent',
                    'service_type' => 'INTERNAL_TRANSFER',
                    'transaction_channel' => 'INTERNAL'
                ]);

                // Recipient Log
                DB::table('message')->insert([
                    'username' => $recipient->username,
                    'amount' => $amount,
                    'message' => '💰 Internal Transfer received from ' . $sender->username . "\n\nAmount: ₦" . number_format($amount, 2),
                    'oldbal' => $recipient->bal,
                    'newbal' => $recipient_new_bal,
                    'habukhan_date' => $date,
                    'plan_status' => 1, // Success
                    'transid' => $transid . '_R',
                    'role' => 'transfer_received', // Or 'deposit' depending on how existing history works
                    'service_type' => 'INTERNAL_TRANSFER',
                    'transaction_channel' => 'INTERNAL'
                ]);

                // Notification handled by NotificationService after commit


                // --- SAVE BENEFICIARY ---
                try {
                    Beneficiary::updateOrCreate(
                    [
                        'user_id' => $sender->id,
                        'service_type' => 'transfer_internal',
                        'identifier' => $recipient->username
                    ],
                    [
                        'name' => $recipient->name ?? $recipient->username,
                        'is_favorite' => filter_var($request->save_beneficiary, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                        'last_used_at' => Carbon::now(),
                    ]
                    );
                }
                catch (\Exception $e) {
                    Log::error('Internal Beneficiary Save Failed: ' . $e->getMessage());
                }

                return ['status' => 'success', 'ref' => $transid, 'sender' => $sender, 'recipient' => $recipient];
            });

            // Record for Tier Limits 
            \App\Services\LimitService::recordTransaction($sender_temp, $amount);

            // SEND NOTIFICATIONS
            try {
                $notifService = new \App\Services\NotificationService();
                // 1. Credit Alert to Recipient
                $notifService->sendInternalCreditNotification(
                    $result['recipient'],
                    $amount,
                    $result['sender']->username
                );
                // 2. Debit Alert to Sender
                $notifService->sendDebitNotification(
                    $result['sender'],
                    $amount,
                    'Transfer to ' . $result['recipient']->username
                );
            }
            catch (\Exception $e) {
                Log::error("Notification Error: " . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer Successful',
                'reference' => $result['ref']
            ]);

        }
        catch (\Exception $e) {
            // Determine if it's a validation error or system error
            $msg = $e->getMessage();
            $code = 400;
            if (!in_array($msg, ['Invalid Transaction PIN', 'Insufficient Balance', 'Recipient not found', 'Cannot transfer to self'])) {
                Log::error("Internal Transfer Error: " . $msg);
                $msg = "Transfer Failed";
                $code = 500;
            }

            return response()->json(['status' => 'fail', 'message' => $msg])->setStatusCode($code);
        }
    }
}