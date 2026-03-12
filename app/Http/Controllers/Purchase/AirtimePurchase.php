<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;
use App\Services\ReceiptService;


class AirtimePurchase extends Controller
{

    public function BuyAirtime(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'user_id' => 'required'
            ]);
            $system = "APP";
            file_put_contents('debug_trace.txt', "Step 1: Start. ID: " . $request->user_id . " Amount: " . $request->amount . "\n", FILE_APPEND);

            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            }
            else {
                $transid = $this->purchase_ref('AIRTIME_');
            }

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('user')->where(['id' => $verified_id, 'status' => 1]);
            if ($check->count() == 1) {
                $d_token = $check->first();
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->apikey;
                    file_put_contents('debug_trace.txt', "Step 2: Auth Success\n", FILE_APPEND);
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            }
            else {
                $accessToken = 'null';
            }
        }
        else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0'
            ], [
                'network.required' => 'Network Id Required',
                'phone.required' => 'Phone Number Required',
                'phone.digits' => 'Phone Number Digits Must Be 11',
            ]);
            $system = config('app.name');
            $transid = $this->purchase_ref('AIRTIME_');
            if ($this->core()->allow_pin == 1) {
                // transaction pin required
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    if (trim($det->pin) == trim($request->pin)) {
                        $accessToken = $det->apikey;
                    }
                    else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Invalid Transaction Pin'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            }
            else {
                // transaction pin not required
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    $accessToken = $det->apikey;
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'An Error Occur'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            // api verification
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'plan_type' => 'required',
                'amount' => 'required|numeric|integer|not_in:0|gt:0',
                'request-id' => 'required|unique:airtime,transid'
            ]);
            $system = "API";
            $id = "request-id";
            $transid = $request->$id;
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }

        $receiptService = new ReceiptService();

        // carry out transaction
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail'
            ])->setStatusCode(403);
        }
        if ($accessToken) {
            $user = DB::table('user')->where(function ($query) use ($accessToken) {
                $query->where('apikey', $accessToken)
                    ->orWhere('app_key', $accessToken)
                    ->orWhere('habukhan_key', $accessToken);
            })->where('status', 1)->sharedLock()->first();
            if ($user) {
                if (DB::table('block')->where(['number' => $request->phone])->count() == 0) {
                    // declear user type
                    if ($user->type == 'SMART') {
                        $user_type = 'smart';
                    }
                    else if ($user->type == 'AGENT') {
                        $user_type = 'agent';
                    }
                    else if ($user->type == 'AWUF') {
                        $user_type = 'awuf';
                    }
                    else if ($user->type == 'API') {
                        $user_type = 'api';
                    }
                    else {
                        $user_type = 'special';
                    }
                    if (DB::table('airtime')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                        // declare all variable
                        $network = $request->network;
                        $phone = $request->phone;
                        if ($request->bypass == true || $request->bypass == 'true') {
                            $bypass = true;
                        }
                        else {
                            $bypass = false;
                        }
                        $plan_type = strtolower($request->plan_type);
                        $amount = $request->amount;

                        // check if network exits before
                        if (DB::table('network')->where('plan_id', $network)->count() == 1) {
                            file_put_contents('debug_trace.txt', "Step 3: Network Found: $network\n", FILE_APPEND);
                            //network details
                            $network_d = DB::table('network')->where('plan_id', $network)->first();

                            if ($plan_type == 'vtu' || $plan_type == 'sns') {
                                // lock services
                                if ($plan_type == 'vtu') {
                                    $habukhan_lock = "network_vtu";
                                }
                                else {
                                    $habukhan_lock = 'network_share';
                                }

                                // check number
                                if ($bypass == false || $request->bypass == 'false') {
                                    $validate = substr($phone, 0, 4);
                                    // ALLOW SANDBOX NUMBER
                                    if ($phone == '08011111111') {
                                        $habukhan_bypass = true;
                                    }
                                    else if ($network_d->network == "MTN") {
                                        if (strpos(" 0702 0703 0713 0704 0706 0707 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validate) == FALSE || strlen($phone) != 11) {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'This is not a MTN Number => ' . $phone
                                            ])->setStatusCode(403);
                                        }
                                        else {
                                            $habukhan_bypass = true;
                                        }
                                    }
                                    else if ($network_d->network == "GLO") {
                                        if (strpos(" 0805 0705 0905 0807 0907 0707 0817 0917 0717 0715 0815 0915 0811 0711 0911 ", $validate) == FALSE || strlen($phone) != 11) {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'This is not a GLO Number =>' . $phone
                                            ])->setStatusCode(403);
                                        }
                                        else {
                                            $habukhan_bypass = true;
                                        }
                                    }
                                    else if ($network_d->network == "AIRTEL") {
                                        if (strpos(" 0904 0802 0902 0702 0808 0911 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validate) == FALSE || strlen($phone) != 11) {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'This is not a AIRTEL Number => ' . $phone
                                            ])->setStatusCode(403);
                                        }
                                        else {
                                            $habukhan_bypass = true;
                                        }
                                    }
                                    else if ($network_d->network == "9MOBILE") {
                                        if (strpos(" 0809 0909 0709 0819 0919 0719 0817 0917 0717 0718 0918 0818 0808 0708 0908 ", $validate) == FALSE || strlen($phone) != 11) {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'This is not a 9MOBILE Number => ' . $phone
                                            ])->setStatusCode(403);
                                        }
                                        else {
                                            $habukhan_bypass = true;
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'Unable to get Network Name'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    $habukhan_bypass = true;
                                }
                                //check if phone number is validated
                                if (substr($phone, 0, 1) == 0) {
                                    // if bypassed
                                    if ($habukhan_bypass == true) {
                                        $habukhan_new_go = true;

                                        if ($habukhan_new_go == true) {
                                            file_put_contents('debug_trace.txt', "Step 4: Limits Passed\n", FILE_APPEND);

                                            if ($plan_type == 'sns') {
                                                $type = 'share';
                                            }
                                            else {
                                                $type = $plan_type;
                                            }
                                            if ($network_d->network == '9MOBILE') {
                                                $real_network = 'mobile';
                                            }
                                            else {
                                                $real_network = $network_d->network;
                                            }
                                            $check_for_me = strtolower($real_network) . "_" . strtolower($type) . "_" . strtolower($user_type);
                                            $discount = DB::table('airtime_discount')->first();
                                            DB::beginTransaction();
                                            $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();
                                            if ($network_d->$habukhan_lock == 1) {
                                                if (is_numeric($user->bal)) {
                                                    if ($amount > 0) {
                                                        $discount_amount = ($request->amount / 100) * $discount->$check_for_me;
                                                        // maximmum
                                                        if ($discount->max_airtime >= $amount) {
                                                            if ($amount >= $discount->min_airtime) {
                                                                if ($user->bal >= $discount_amount) {
                                                                    file_put_contents('debug_trace.txt', "Step 5: Balance Sufficient\n", FILE_APPEND);
                                                                    $debit = $user->bal - $discount_amount;
                                                                    $refund = $debit + $discount_amount;

                                                                    $receiptData = [
                                                                        'amount' => $amount,
                                                                        'recipient' => $phone,
                                                                        'reference' => $transid,
                                                                        'status' => 'PENDING',
                                                                        'provider' => $network_d->network,
                                                                        'transaction_channel' => 'EXTERNAL'
                                                                    ];

                                                                    $pendingMessage = "⏳ Processing ₦{$amount} airtime for {$phone}...";

                                                                    $trans_history = [
                                                                        'username' => $user->username,
                                                                        'amount' => $amount,
                                                                        'message' => $pendingMessage,
                                                                        'phone_account' => $phone,
                                                                        'oldbal' => $user->bal,
                                                                        'newbal' => $debit,
                                                                        'habukhan_date' => $this->system_date(),
                                                                        'plan_status' => 0,
                                                                        'transid' => $transid,
                                                                        'role' => 'airtime',
                                                                        'service_type' => 'AIRTIME',
                                                                        'transaction_channel' => 'EXTERNAL'
                                                                    ];
                                                                    $airtime_history = [
                                                                        'username' => $user->username,
                                                                        'network' => $network_d->network,
                                                                        'network_type' => strtoupper($plan_type),
                                                                        'amount' => $amount,
                                                                        'oldbal' => $user->bal,
                                                                        'newbal' => $debit,
                                                                        'discount' => $discount_amount,
                                                                        'transid' => $transid,
                                                                        'plan_date' => $this->system_date(),
                                                                        'plan_status' => 0,
                                                                        'plan_phone' => $phone,
                                                                        'system' => $system
                                                                    ];
                                                                    if (DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
                                                                        DB::commit();
                                                                        file_put_contents('debug_trace.txt', "Step 6: Debit Success\n", FILE_APPEND);

                                                                        if ($this->inserting_data('message', $trans_history) and $this->inserting_data('airtime', $airtime_history)) {
                                                                            // purchase data now
                                                                            $sending_data = [
                                                                                'transid' => $transid,
                                                                                'username' => $user->username
                                                                            ];
                                                                            $habukhanm = new AirtimeSend();
                                                                            $airtime_sel = DB::table('airtime_sel')->first();
                                                                            $v_key = strtolower($real_network) . "_" . ($plan_type == 'sns' ? 'share' : 'vtu');
                                                                            $check_now = $airtime_sel->$v_key ?? 'Habukhan1';
                                                                            $response = $habukhanm->$check_now($sending_data);
                                                                            if (!empty($response)) {
                                                                                if ($response == 'success') {
                                                                                    // --- SMART BENEFICIARY SAVE ---
                                                                                    try {
                                                                                        Beneficiary::updateOrCreate(
                                                                                        [
                                                                                            'user_id' => $user->id,
                                                                                            'service_type' => 'airtime',
                                                                                            'identifier' => $phone
                                                                                        ],
                                                                                        [
                                                                                            'network_or_provider' => $network_d->network,
                                                                                            'last_used_at' => Carbon::now(),
                                                                                        ]
                                                                                        );
                                                                                    }
                                                                                    catch (\Exception $e) {
                                                                                        Log::error('Airtime Beneficiary Save Failed: ' . $e->getMessage());
                                                                                    }

                                                                                    $successMessage = $receiptService->getFullMessage('AIRTIME', [
                                                                                        'amount' => $amount,
                                                                                        'recipient' => $phone,
                                                                                        'reference' => $transid,
                                                                                        'status' => 'SUCCESS',
                                                                                        'provider' => $network_d->network
                                                                                    ]);

                                                                                    // state success transaction
                                                                                    DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                                                                                    DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);

                                                                                    // SEND NOTIFICATION
                                                                                    try {
                                                                                        (new \App\Services\NotificationService())->sendAirtimeNotification(
                                                                                            $user,
                                                                                            $amount,
                                                                                            $network_d->network,
                                                                                            $phone,
                                                                                            $transid
                                                                                        );
                                                                                    }
                                                                                    catch (\Exception $e) {
                                                                                        \Log::error("Airtime Notification Error: " . $e->getMessage());
                                                                                    }

                                                                                    return response()->json([
                                                                                        'network' => $network_d->network,
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $amount,
                                                                                        'transid' => $transid,
                                                                                        'discount' => $discount_amount,
                                                                                        'status' => 'success',
                                                                                        'message' => 'successfully purchase ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount,
                                                                                        'phone_number' => $phone,
                                                                                        'oldbal' => $user->bal,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'plan_type' => strtoupper($plan_type),
                                                                                        'wallet_vending' => "wallet"
                                                                                    ]);
                                                                                }
                                                                                else if ($response == 'process') {
                                                                                    return response()->json([
                                                                                        'network' => $network_d->network,
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $amount,
                                                                                        'discount' => $discount_amount,
                                                                                        'status' => 'process',
                                                                                        'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                                                                                        'phone_number' => $phone,
                                                                                        'oldbal' => $user->bal,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'wallet_vending' => 'wallet',
                                                                                        'plan_type' => strtoupper($plan_type),
                                                                                    ]);
                                                                                }
                                                                                else if ($response == 'fail') {
                                                                                    $check_fail = DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->first();
                                                                                    if ($check_fail->plan_status != 2) {
                                                                                        $admin_refund = DB::table('user')->where(['id' => $user->id])->first();
                                                                                        DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $admin_refund->bal + $discount_amount]);

                                                                                        $failMessage = "❌ Airtime Purchase Failed\n\nYou attempted to purchase ₦{$amount} airtime for {$phone} but the transaction failed. Your wallet has been refunded.";

                                                                                        DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'message' => $failMessage, 'newbal' => $refund]);
                                                                                        DB::table('airtime')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund]);
                                                                                    }
                                                                                    return response()->json([
                                                                                        'network' => $network_d->network,
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $amount,
                                                                                        'discount' => $discount_amount,
                                                                                        'status' => 'fail',
                                                                                        'message' => 'Transaction fail ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone . ' , ₦' . $amount,
                                                                                        'phone_number' => $phone,
                                                                                        'oldbal' => $user->bal,
                                                                                        'newbal' => $refund,
                                                                                        'system' => $system,
                                                                                        'plan_type' => strtoupper($plan_type),
                                                                                        'wallet_vending' => "wallet"
                                                                                    ]);
                                                                                }
                                                                                else {
                                                                                    return response()->json([
                                                                                        'network' => $network_d->network,
                                                                                        'request-id' => $transid,
                                                                                        'amount' => $amount,
                                                                                        'discount' => $discount_amount,
                                                                                        'status' => 'process',
                                                                                        'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                                                                                        'phone_number' => $phone,
                                                                                        'oldbal' => $user->bal,
                                                                                        'newbal' => $debit,
                                                                                        'system' => $system,
                                                                                        'wallet_vending' => 'wallet',
                                                                                        'plan_type' => strtoupper($plan_type),
                                                                                    ]);
                                                                                }
                                                                            }
                                                                            else {
                                                                                return response()->json([
                                                                                    'network' => $network_d->network,
                                                                                    'request-id' => $transid,
                                                                                    'amount' => $amount,
                                                                                    'discount' => $discount_amount,
                                                                                    'status' => 'process',
                                                                                    'message' => 'Transaction on process ' . $network_d->network . ' ' . strtoupper($type) . ' to ' . $phone,
                                                                                    'phone_number' => $phone,
                                                                                    'oldbal' => $user->bal,
                                                                                    'newbal' => $debit,
                                                                                    'system' => $system,
                                                                                    'wallet_vending' => 'wallet',
                                                                                    'plan_type' => strtoupper($plan_type),
                                                                                ]);
                                                                            }
                                                                        }
                                                                        else {
                                                                            // refund user here
                                                                            DB::table('message')->where('transid', $transid)->delete();
                                                                            DB::table('airtime')->where('transid', $transid)->delete();
                                                                            DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $refund]);
                                                                            return response()->json([
                                                                                'status' => 'fail',
                                                                                'message' => 'kindly re try after some mins'
                                                                            ])->setStatusCode(403);
                                                                        }
                                                                    }
                                                                    else {
                                                                        return response()->json([
                                                                            'status' => 'fail',
                                                                            'message' => 'Unable to debit user right now'
                                                                        ])->setStatusCode(403);
                                                                    }
                                                                }
                                                                else {
                                                                    return response()->json([
                                                                        'status' => 'fail',
                                                                        'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->bal, 2)
                                                                    ])->setStatusCode(403);
                                                                }

                                                            }
                                                            else {
                                                                return response()->json([
                                                                    'status' => 'fail',
                                                                    'message' => 'Minimum Airtime Purchase for this account is => ₦' . number_format($discount->min_airtime, 2)
                                                                ])->setStatusCode(403);
                                                            }

                                                        }
                                                        else {
                                                            return response()->json([
                                                                'status' => 'fail',
                                                                'message' => 'Maximum Airtime Purchase for this account is => ₦' . number_format($discount->max_airtime, 2)
                                                            ])->setStatusCode(403);
                                                        }
                                                    }
                                                    else {
                                                        return response()->json([
                                                            'status' => 'fail',
                                                            'message' => 'invalid amount'
                                                        ])->setStatusCode(403);
                                                    }
                                                }
                                                else {
                                                    return response()->json([
                                                        'status' => 'fail',
                                                        'message' => 'Unknown Account Balance'
                                                    ])->setStatusCode(403);
                                                }
                                            }
                                            else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => $network_d->network . ' ' . strtoupper($plan_type) . ' is not available right now'
                                                ])->setStatusCode(403);
                                            }
                                        }
                                        else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'You have Reach Daily Transaction Limit Kindly Message the Admin To Upgrade Your Account'
                                            ])->setStatusCode(403);
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'Phone Number Bypass Failed'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Invalid Phone Number => ' . $phone
                                    ])->setStatusCode(403);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Invalid Network Plan Type'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Invalid Network ID'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Transaction Plan Id Exits'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Number Block'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid Access Token'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Authorization Header Token Required'
            ])->setStatusCode(403);
        }
    }
}