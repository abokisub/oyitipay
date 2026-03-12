<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Beneficiary;
use App\Services\ReceiptService;

class DataPurchase extends Controller
{

    public function BuyData(Request $request)
    {
        // Basic logging to see if request reaches this point
        \Log::info('🚨 DATA PURCHASE DEBUG - Request reached BuyData method');
        \Log::info('🚨 DATA PURCHASE DEBUG - Request method: ' . $request->method());
        \Log::info('🚨 DATA PURCHASE DEBUG - Request URL: ' . $request->url());
        \Log::info('🚨 DATA PURCHASE DEBUG - Authorization header: ' . $request->header('Authorization'));

        // check where the response coming from
        $explode_url = explode(',', config('app.habukhan_app_key'));
        \Log::info('🚨 DATA PURCHASE DEBUG - Origin header: ' . $request->headers->get('origin'));
        \Log::info('🚨 DATA PURCHASE DEBUG - HABUKHAN_APP_KEY: ' . config('app.habukhan_app_key'));
        \Log::info('🚨 DATA PURCHASE DEBUG - Exploded HABUKHAN_APP_KEY: ' . json_encode($explode_url));
        \Log::info('🚨 DATA PURCHASE DEBUG - Origin in array: ' . (in_array($request->headers->get('origin'), $explode_url) ? 'TRUE' : 'FALSE'));

        // Prioritize device key authentication for mobile apps
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            \Log::info('🚨 DATA PURCHASE DEBUG - Using device key authentication');
            \Log::info('🚨 DATA PURCHASE DEBUG - Device key comparison: ' . config('app.habukhan_device_key') . ' == ' . $request->header('Authorization'));
            \Log::info('🚨 DATA PURCHASE DEBUG - Comparison result: ' . (config('app.habukhan_device_key') == $request->header('Authorization') ? 'TRUE' : 'FALSE'));

            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'data_plan' => 'required',
                'user_id' => 'required',
                'pin' => 'required|numeric|digits:4'
            ]);
            $system = "APP";
            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            }
            else {
                $transid = $this->purchase_ref('DATA_');
            }

            // Debug logging for mobile app data purchase
            \Log::info('🚨 DATA PURCHASE DEBUG - Request received:', [
                'user_id' => $request->user_id,
                'pin' => $request->pin,
                'pin_type' => gettype($request->pin),
                'authorization' => $request->header('Authorization'),
                'device_key' => config('app.habukhan_device_key')
            ]);

            $verified_user_id = $this->verifyapptoken($request->user_id);
            \Log::info('🚨 DATA PURCHASE DEBUG - verifyapptoken result:', [
                'input_user_id' => $request->user_id,
                'verified_user_id' => $verified_user_id,
                'verified_user_id_type' => gettype($verified_user_id)
            ]);

            if (DB::table('user')->where(['id' => $verified_user_id, 'status' => 1])->count() == 1) {
                $d_token = DB::table('user')->where(['id' => $verified_user_id, 'status' => 1])->first();

                \Log::info('🚨 DATA PURCHASE DEBUG - User found:', [
                    'user_id' => $d_token->id,
                    'username' => $d_token->username,
                    'stored_pin' => $d_token->pin,
                    'stored_pin_type' => gettype($d_token->pin),
                    'sent_pin' => $request->pin,
                    'sent_pin_type' => gettype($request->pin),
                    'pin_match' => ($d_token->pin == $request->pin),
                    'pin_strict_match' => ($d_token->pin === $request->pin)
                ]);

                // Verify PIN for mobile app
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->apikey;
                    \Log::info('🚨 DATA PURCHASE DEBUG - PIN validation successful');
                }
                else {
                    \Log::error('🚨 DATA PURCHASE DEBUG - PIN validation failed:', [
                        'stored_pin' => $d_token->pin,
                        'sent_pin' => $request->pin,
                        'pin_match' => ($d_token->pin == $request->pin),
                        'pin_strict_match' => ($d_token->pin === $request->pin)
                    ]);
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            }
            else {
                \Log::error('🚨 DATA PURCHASE DEBUG - User not found:', [
                    'user_id' => $request->user_id,
                    'verified_user_id' => $verified_user_id,
                    'user_count' => DB::table('user')->where(['id' => $verified_user_id, 'status' => 1])->count()
                ]);
                $accessToken = 'null';
            }
        }
        else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url) || $request->headers->get('origin') === $request->getSchemeAndHttpHost()) {
            \Log::info('🚨 DATA PURCHASE DEBUG - Using origin-based authentication');
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'data_plan' => 'required'
            ], [
                'network.required' => 'Network Id Required',
                'phone.required' => 'Phone Number Required',
                'phone.digits' => 'Phone Number Digits Must Be 11',
                'data_plan.required' => 'Data Plan ID Required'
            ]);
            $system = config('app.name');
            $transid = $this->purchase_ref('DATA_');
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
        //
        }
        else {
            // New: Accept per-user API key in Authorization header (with or without 'Token ' prefix)
            $authHeader = $request->header('Authorization');
            if (strpos($authHeader, 'Token ') === 0) {
                $authHeader = substr($authHeader, 6);
            }
            $accessToken = trim($authHeader);
            $validator = Validator::make($request->all(), [
                'network' => 'required',
                'phone' => 'required|numeric|digits:11',
                'bypass' => 'required',
                'data_plan' => 'required',
                'request-id' => 'required|unique:data,transid'
            ]);
            $authHeader = $request->header('Authorization');
            if (strpos($authHeader, 'Token ') === 0) {
                $authHeader = substr($authHeader, 6);
            }
            $accessToken = trim($authHeader);
        }

        $receiptService = new ReceiptService();

        if (isset($accessToken)) {
            $user = DB::table('user')->where(function ($query) use ($accessToken) {
                $query->where('apikey', $accessToken)
                    ->orWhere('app_key', $accessToken)
                    ->orWhere('habukhan_key', $accessToken);
            })->where('status', 1)->first();
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
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 'fail'
                        ])->setStatusCode(403);
                    }
                    else {
                        // now where transaction begins
                        if (DB::table('data')->where('transid', $transid)->count() == 0 && DB::table('message')->where('transid', $transid)->count() == 0) {
                            // declare all variable
                            $network = $request->network;
                            $phone = $request->phone;

                            $bypass = true;

                            $plan_id = $request->data_plan;
                            // check if network exits before
                            if (DB::table('network')->where('plan_id', $network)->count() == 1) {
                                //network details
                                $network_d = DB::table('network')->where('plan_id', $network)->first();
                                if (DB::table('data_plan')->where(['network' => $network_d->network, 'plan_id' => $plan_id, 'plan_status' => 1])->count() == 1) {
                                    // pland details
                                    $plan_d = DB::table('data_plan')->where(['network' => $network_d->network, 'plan_id' => $plan_id, 'plan_status' => 1])->first();
                                    // lock services
                                    if ($plan_d->plan_type == 'GIFTING') {
                                        $habukhan_lock = "network_g";
                                        if ($network_d->network == '9MOBILE') {
                                            $wallet_bal = "mobile_g_bal";
                                            $vending = "mobile_g";
                                        }
                                        else {
                                            $wallet_bal = strtolower($network_d->network) . "_g_bal";
                                            $vending = strtolower($network_d->network) . "_g";
                                        }
                                    }
                                    else if ($plan_d->plan_type == 'COOPERATE GIFTING') {
                                        $habukhan_lock = "network_cg";
                                        if ($network_d->network == '9MOBILE') {
                                            $wallet_bal = "mobile_cg_bal";
                                            $vending = "mobile_cg";
                                        }
                                        else {
                                            $wallet_bal = strtolower($network_d->network) . "_cg_bal";
                                            $vending = strtolower($network_d->network) . "_cg";
                                        }
                                    }
                                    else if ($plan_d->plan_type == 'SME') {
                                        $habukhan_lock = "network_sme";
                                        if ($network_d->network == '9MOBILE') {
                                            $wallet_bal = "mobile_sme_bal";
                                            $vending = "mobile_sme";
                                        }
                                        else {
                                            $wallet_bal = strtolower($network_d->network) . "_sme_bal";
                                            $vending = strtolower($network_d->network) . "_sme";
                                        }
                                    }
                                    else if ($plan_d->plan_type == 'SME 2') {
                                        $habukhan_lock = "network_sme2";
                                        if ($network_d->network == '9MOBILE') {
                                            $wallet_bal = "mobile_sme2_bal";
                                            $vending = "mobile_sme2";
                                        }
                                        else {
                                            $wallet_bal = strtolower($network_d->network) . "_sme2_bal";
                                            $vending = strtolower($network_d->network) . "_sme2";
                                        }
                                    }
                                    else if ($plan_d->plan_type == 'DATASHARE') {
                                        $habukhan_lock = "network_datashare";
                                        if ($network_d->network == '9MOBILE') {
                                            $wallet_bal = "mobile_datashare_bal";
                                            $vending = "mobile_datashare";
                                        }
                                        else {
                                            $wallet_bal = strtolower($network_d->network) . "_datashare_bal";
                                            $vending = strtolower($network_d->network) . "_datashare";
                                        }
                                    }
                                    else {
                                        $habukhan_lock = null;
                                        $wallet_bal = null;
                                        $vending = null;
                                    }
                                    if (!empty($habukhan_lock)) {
                                        if ($network_d->$habukhan_lock == 1) {
                                            if (substr($phone, 0, 1) == 0) {
                                                // check number
                                                if ($bypass == false) {
                                                    $validate = substr($phone, 0, 4);
                                                    if ($network_d->network == "MTN") {
                                                        if (strpos(" 0702 0703 0713 0704 0706 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validate) == FALSE || strlen($phone) != 11) {
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
                                                        if (strpos(" 0904 0802 0902 0702 0808 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validate) == FALSE || strlen($phone) != 11) {
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
                                                // if bypassed
                                                if ($habukhan_bypass == true) {
                                                    $habukhan_new_go = true;
                                                    // re-check the account balance again 
                                                    DB::beginTransaction();
                                                    $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();
                                                    if ($habukhan_new_go == true) {
                                                        // check network vending
                                                        if (DB::table('wallet_funding')->where('username', $user->username)->count() == 1) {
                                                            $wallet_store = DB::table('wallet_funding')->where('username', $user->username)->first();
                                                            if (!empty($vending)) {
                                                                if (!empty($wallet_bal)) {
                                                                    if (isset($wallet_store->$vending) && $wallet_store->$vending == 1) {
                                                                        $vending_system = strtoupper($vending);
                                                                        $user_balance = $wallet_store->$wallet_bal ?? 0;
                                                                    }
                                                                    else {
                                                                        $vending_system = "wallet";
                                                                        $user_balance = $user->bal;
                                                                    }
                                                                    // checking if amount plan is decleared
                                                                    if (!empty($plan_d->$user_type)) {
                                                                        if (is_numeric($user_balance)) {
                                                                            if ($user_balance > 0) {
                                                                                if ($user_balance >= $plan_d->$user_type) {
                                                                                    // debit the user and re-funcd
                                                                                    $debit = $user_balance - $plan_d->$user_type;
                                                                                    if ($vending_system == 'wallet') {
                                                                                        $habukhan_debit = DB::table('user')->where('id', $user->id)->update(['bal' => $debit]);
                                                                                    }
                                                                                    else {
                                                                                        $habukhan_debit = DB::table('wallet_funding')->where('username', $user->username)->update([$wallet_bal => $debit]);
                                                                                    }
                                                                                    if ($habukhan_debit) {
                                                                                        DB::commit();
                                                                                        $trans_history = [
                                                                                            'username' => $user->username,
                                                                                            'amount' => $plan_d->$user_type,
                                                                                            'message' => "⏳ Processing " . $network_d->network . " " . $plan_d->plan_name . $plan_d->plan_size . " to " . $phone . "...",
                                                                                            'phone_account' => $phone,
                                                                                            'oldbal' => $user_balance,
                                                                                            'newbal' => $debit,
                                                                                            'habukhan_date' => $this->system_date(),
                                                                                            'plan_status' => 0,
                                                                                            'transid' => $transid,
                                                                                            'role' => 'data',
                                                                                            'service_type' => 'DATA',
                                                                                            'transaction_channel' => 'EXTERNAL'
                                                                                        ];
                                                                                        $data_trans = [
                                                                                            'username' => $user->username,
                                                                                            'network_type' => $plan_d->plan_type,
                                                                                            'network' => $network_d->network,
                                                                                            'plan_name' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                            'amount' => $plan_d->$user_type,
                                                                                            'plan_status' => 0,
                                                                                            'transid' => $transid,
                                                                                            'plan_phone' => $phone,
                                                                                            'plan_date' => $this->system_date(),
                                                                                            'oldbal' => $user_balance,
                                                                                            'newbal' => $debit,
                                                                                            'system' => $system,
                                                                                            'wallet' => $vending_system
                                                                                        ];
                                                                                        if ($this->inserting_data('message', $trans_history) and $this->inserting_data('data', $data_trans)) {
                                                                                            // purchase data now
                                                                                            $sending_data = [
                                                                                                'purchase_plan' => $request->data_plan,
                                                                                                'transid' => $transid,
                                                                                                'username' => $user->username
                                                                                            ];
                                                                                            $habukhanm = new DataSend();
                                                                                            $data_sel = DB::table('data_sel')->first();
                                                                                            $check_now = $data_sel->$vending;
                                                                                            \Log::info('🚨 DATA VENDING DEBUG:', ['vending' => $vending, 'method' => $check_now, 'plan_id' => $request->data_plan]);

                                                                                            // UNIVERSAL SMART SWITCH ACTIVATION
                                                                                            $response = DataSend::SmartAttempt($check_now, $sending_data);

                                                                                            \Log::info('🚨 DATA VENDING RESPONSE:', ['response' => $response]);
                                                                                            
                                                                                            // Handle null/empty response from provider (timeout/failure)
                                                                                            if ($response === null || $response === '') {
                                                                                                \Log::error('🚨 DATA VENDING ERROR: Provider returned null/empty response', [
                                                                                                    'transid' => $transid,
                                                                                                    'vending' => $vending,
                                                                                                    'phone' => $phone
                                                                                                ]);
                                                                                                
                                                                                                // Refund user
                                                                                                if ($vending_system == 'wallet') {
                                                                                                    $admin_refund = DB::table('user')->where(['id' => $user->id])->first();
                                                                                                    DB::table('user')->where(['id' => $user->id])->update(['bal' => $admin_refund->bal + $plan_d->$user_type]);
                                                                                                    $refund_bal = $admin_refund->bal + $plan_d->$user_type;
                                                                                                } else {
                                                                                                    $admin_refund = DB::table('wallet_funding')->where(['username' => $user->username])->first();
                                                                                                    DB::table('wallet_funding')->where(['username' => $user->username])->update([$wallet_bal => $admin_refund->$wallet_bal + $plan_d->$user_type]);
                                                                                                    $refund_bal = $admin_refund->$wallet_bal + $plan_d->$user_type;
                                                                                                }
                                                                                                
                                                                                                $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $plan_d->plan_name . $plan_d->plan_size . " for {$phone} but the provider did not respond. Your wallet has been refunded.";
                                                                                                
                                                                                                // Update transaction status
                                                                                                DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->update([
                                                                                                    'plan_status' => 2,
                                                                                                    'oldbal' => $user_balance,
                                                                                                    'newbal' => $refund_bal
                                                                                                ]);
                                                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update([
                                                                                                    'plan_status' => 2,
                                                                                                    'oldbal' => $user_balance,
                                                                                                    'newbal' => $refund_bal,
                                                                                                    'message' => $failMessage
                                                                                                ]);
                                                                                                
                                                                                                return response()->json([
                                                                                                    'network' => $network_d->network,
                                                                                                    'request-id' => $transid,
                                                                                                    'amount' => $plan_d->$user_type,
                                                                                                    'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                    'status' => 'fail',
                                                                                                    'message' => 'Provider timeout - Transaction failed. Your wallet has been refunded.',
                                                                                                    'phone_number' => $phone,
                                                                                                    'oldbal' => $user_balance,
                                                                                                    'newbal' => $refund_bal,
                                                                                                    'system' => $system,
                                                                                                    'plan_type' => $plan_d->plan_type,
                                                                                                    'wallet_vending' => $vending_system
                                                                                                ]);
                                                                                            }
                                                                                            
                                                                                            if (!empty($response)) {
                                                                                                if ($response == 'success') {
                                                                                                    // --- SMART BENEFICIARY SAVE ---
                                                                                                    try {
                                                                                                        Beneficiary::updateOrCreate(
                                                                                                        [
                                                                                                            'user_id' => $user->id,
                                                                                                            'service_type' => 'data',
                                                                                                            'identifier' => $phone
                                                                                                        ],
                                                                                                        [
                                                                                                            'network_or_provider' => $network_d->network,
                                                                                                            'last_used_at' => Carbon::now(),
                                                                                                        ]
                                                                                                        );
                                                                                                    }
                                                                                                    catch (\Exception $e) {
                                                                                                        \Log::error('Data Beneficiary Save Failed: ' . $e->getMessage());
                                                                                                    }

                                                                                                    $data_response = DB::table('data')->where(['transid' => $transid])->first();
                                                                                                    if ($data_response->api_response != null) {
                                                                                                        $api_response = $data_response->api_response;
                                                                                                    }
                                                                                                    else {
                                                                                                        $api_response = null;
                                                                                                    }

                                                                                                    // fake real time response
                                                                                                    if ($network_d->network == 'AIRTEL') {
                                                                                                        $message = "You have been gifted " . $plan_d->plan_name . $plan_d->plan_size . ' of Data from ' . config('app.name') . ' Technology';
                                                                                                    }
                                                                                                    else if ($network_d->network == 'MTN' && $plan_d->plan_type == 'SME') {
                                                                                                        $message = "Dear Customer, You have successfully shared " . $plan_d->plan_name . $plan_d->plan_size . " Data to 234" . substr($phone, -10);
                                                                                                    }
                                                                                                    else if ($network_d->network == 'MTN' && $plan_d->plan_type == 'COOPERATE GIFTING') {
                                                                                                        $message = "Dear Customer, You have gifted " . $plan_d->plan_name . $plan_d->plan_size . ", please dial *460*261# to check your balance. Thankyou.";
                                                                                                    }
                                                                                                    else if ($network_d->network == 'MTN' && $plan_d->plan_type == 'GIFTING') {
                                                                                                        $message = "Yello! You have gifted " . $plan_d->plan_name . $plan_d->plan_size . " to 234" . substr($phone, -10) . ". Share link https://mtnapp.page.link/myMTNNGApp with 234" . substr($phone, -10) . " to download the new MyMTN app for exciting offers.";
                                                                                                    }
                                                                                                    else if ($network_d->network == 'GLO') {
                                                                                                        $message = "You have successfully gifted " . $plan_d->plan_name . $plan_d->plan_size . ' Oneoff to 234' . substr($phone, -10);
                                                                                                    }
                                                                                                    else {
                                                                                                        $message = "You have been gifted " . $plan_d->plan_name . $plan_d->plan_size;
                                                                                                    }
                                                                                                    $receiptService = new \App\Services\ReceiptService();
                                                                                                    $successMessage = $receiptService->getFullMessage('DATA', [
                                                                                                        'plan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                        'recipient' => $phone,
                                                                                                        'reference' => $transid,
                                                                                                        'status' => 'SUCCESS',
                                                                                                        'provider' => $network_d->network
                                                                                                    ]);

                                                                                                    // state success transaction
                                                                                                    DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                                                                                                    DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, ]);

                                                                                                    // SEND NOTIFICATION
                                                                                                    try {
                                                                                                        (new \App\Services\NotificationService())->sendDataNotification(
                                                                                                            $user,
                                                                                                            $plan_d->$user_type,
                                                                                                            $network_d->network,
                                                                                                            $plan_d->plan_name . $plan_d->plan_size,
                                                                                                            $phone,
                                                                                                            $transid
                                                                                                        );
                                                                                                    }
                                                                                                    catch (\Exception $e) {
                                                                                                        \Log::error("Data Notification Error: " . $e->getMessage());
                                                                                                    }


                                                                                                    if ($api_response) {
                                                                                                        $ch = curl_init();
                                                                                                        curl_setopt($ch, CURLOPT_URL, $user->webhook);
                                                                                                        curl_setopt($ch, CURLOPT_POST, 1);
                                                                                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => 'success', 'request-id' => $transid, 'response' => $api_response])); //Post Fields
                                                                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                                                                        curl_exec($ch);
                                                                                                        curl_close($ch);
                                                                                                    }

                                                                                                    return response()->json([
                                                                                                        'network' => $network_d->network,
                                                                                                        'request-id' => $transid,
                                                                                                        'amount' => $plan_d->$user_type,
                                                                                                        'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                        'status' => 'success',
                                                                                                        'transid' => $transid,
                                                                                                        'message' => $message,
                                                                                                        'phone_number' => $phone,
                                                                                                        'oldbal' => $user_balance,
                                                                                                        'newbal' => $debit,
                                                                                                        'system' => $system,
                                                                                                        'plan_type' => $plan_d->plan_type,
                                                                                                        'wallet_vending' => $vending_system,
                                                                                                        'response' => $api_response
                                                                                                    ]);
                                                                                                }
                                                                                                else if ($response == 'process') {
                                                                                                    return response()->json([
                                                                                                        'network' => $network_d->network,
                                                                                                        'request-id' => $transid,
                                                                                                        'amount' => $plan_d->$user_type,
                                                                                                        'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                        'status' => 'process',
                                                                                                        'message' => 'Transaction on process ' . $network_d->network . ' ' . $plan_d->plan_type . ' ' . $plan_d->plan_name . $plan_d->plan_size . ' to ' . $phone,
                                                                                                        'phone_number' => $phone,
                                                                                                        'oldbal' => $user_balance,
                                                                                                        'newbal' => $debit,
                                                                                                        'system' => $system,
                                                                                                        'wallet_vending' => $vending_system,
                                                                                                        'plan_type' => $plan_d->plan_type,
                                                                                                    ]);
                                                                                                }
                                                                                                else if ($response == 'fail') {
                                                                                                    $check_fail = DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->first();

                                                                                                    if ($vending_system == 'wallet') {
                                                                                                        $admin_refund = DB::table('user')->where(['id' => $user->id])->first();
                                                                                                        $refund_bal = $admin_refund->bal;
                                                                                                    }
                                                                                                    else {
                                                                                                        $admin_refund = DB::table('wallet_funding')->where(['username' => $user->username])->first();

                                                                                                        $refund_bal = $admin_refund->$wallet_bal;
                                                                                                    }

                                                                                                    if ($check_fail->plan_status != 2) {
                                                                                                        // refund user here
                                                                                                        if ($vending_system == 'wallet') {
                                                                                                            $admin_refund = DB::table('user')->where(['id' => $user->id])->first();
                                                                                                            if (DB::table('user')->where(['id' => $user->id])->update(['bal' => $admin_refund->bal + $plan_d->$user_type])) {
                                                                                                                $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $plan_d->plan_name . $plan_d->plan_size . " for {$phone} but the transaction failed. Your wallet has been refunded.";
                                                                                                                // real transaction
                                                                                                                DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $admin_refund->bal + $plan_d->$user_type]);
                                                                                                                // trans history
                                                                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $admin_refund->bal + $plan_d->$user_type, 'message' => $failMessage]);
                                                                                                            }
                                                                                                        }
                                                                                                        else {

                                                                                                            $admin_refund = DB::table('wallet_funding')->where(['username' => $user->username])->first();
                                                                                                            if (DB::table('wallet_funding')->where(['username' => $user->username])->update([$wallet_bal => $admin_refund->$wallet_bal + $plan_d->$user_type])) {
                                                                                                                $failMessage = "❌ Data Purchase Failed\n\nYou attempted to purchase " . $plan_d->plan_name . $plan_d->plan_size . " for {$phone} but the transaction failed. Your wallet has been refunded.";

                                                                                                                // real transaction
                                                                                                                DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $admin_refund->$wallet_bal + $plan_d->$user_type]);
                                                                                                                // trans history
                                                                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user_balance, 'newbal' => $admin_refund->$wallet_bal + $plan_d->$user_type, 'message' => $failMessage]);
                                                                                                            }
                                                                                                        }
                                                                                                    }


                                                                                                    $data_response = DB::table('data')->where(['transid' => $transid])->first();
                                                                                                    if ($data_response->api_response != null) {
                                                                                                        $api_response = $data_response->api_response;
                                                                                                    }
                                                                                                    else {
                                                                                                        $api_response = null;
                                                                                                    }


                                                                                                    if ($api_response) {
                                                                                                        $ch = curl_init();
                                                                                                        curl_setopt($ch, CURLOPT_URL, $user->webhook);
                                                                                                        curl_setopt($ch, CURLOPT_POST, 1);
                                                                                                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => 'fail', 'request-id' => $transid, 'response' => $api_response])); //Post Fields
                                                                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                                                                        curl_exec($ch);
                                                                                                        curl_close($ch);
                                                                                                    }

                                                                                                    return response()->json([
                                                                                                        'network' => $network_d->network,
                                                                                                        'request-id' => $transid,
                                                                                                        'amount' => $plan_d->$user_type,
                                                                                                        'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                        'status' => 'fail',
                                                                                                        'message' => 'Transaction  fail ' . $network_d->network . ' ' . $plan_d->plan_type . ' ' . $plan_d->plan_name . $plan_d->plan_size . ' to ' . $phone,
                                                                                                        'phone_number' => $phone,
                                                                                                        'oldbal' => $user_balance,
                                                                                                        'newbal' => $refund_bal + $plan_d->$user_type,
                                                                                                        'system' => $system,
                                                                                                        'plan_type' => $plan_d->plan_type,
                                                                                                        'wallet_vending' => $vending_system,
                                                                                                        'response' => $api_response
                                                                                                    ]);
                                                                                                }
                                                                                                else {
                                                                                                    return response()->json([
                                                                                                        'network' => $network_d->network,
                                                                                                        'request-id' => $transid,
                                                                                                        'amount' => $plan_d->$user_type,
                                                                                                        'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                        'status' => 'process',
                                                                                                        'message' => 'Transaction on process ' . $network_d->network . ' ' . $plan_d->plan_type . ' ' . $plan_d->plan_name . $plan_d->plan_size . ' to ' . $phone,
                                                                                                        'phone_number' => $phone,
                                                                                                        'oldbal' => $user_balance,
                                                                                                        'newbal' => $debit,
                                                                                                        'system' => $system,
                                                                                                        'wallet_vending' => $vending_system,
                                                                                                        'plan_type' => $plan_d->plan_type,
                                                                                                    ]);
                                                                                                }
                                                                                            }
                                                                                            else {
                                                                                                return response()->json([
                                                                                                    'network' => $network_d->network,
                                                                                                    'request-id' => $transid,
                                                                                                    'amount' => $plan_d->$user_type,
                                                                                                    'dataplan' => $plan_d->plan_name . $plan_d->plan_size,
                                                                                                    'status' => 'process',
                                                                                                    'message' => 'Transaction on process ' . $network_d->network . ' ' . $plan_d->plan_type . ' ' . $plan_d->plan_name . $plan_d->plan_size . ' to ' . $phone,
                                                                                                    'phone_number' => $phone,
                                                                                                    'oldbal' => $user_balance,
                                                                                                    'newbal' => $debit,
                                                                                                    'system' => $system,
                                                                                                    'wallet_vending' => $vending_system,
                                                                                                    'plan_type' => $plan_d->plan_type,
                                                                                                ]);
                                                                                            }
                                                                                        }
                                                                                        else {
                                                                                            // // refund
                                                                                            // if ($vending_system == 'wallet') {
                                                                                            //     DB::table('user')->where('id', $user->id)->update(['bal' =>  $admin_refund->bal + $plan_d->$user_type]);
                                                                                            // } else {
                                                                                            //     DB::table('wallet_funding')->where('username', $user->username)->update([$wallet_bal =>  $admin_refund->bal + $plan_d->$user_type]);
                                                                                            // }
                                                                                            // delete transaction if exits before
                                                                                            // DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->delete();
                                                                                            // DB::table('data')->where(['username' => $user->username, 'transid' => $transid])->delete();
                                                                                            return response()->json([
                                                                                                'status' => 'fail',
                                                                                                'message' => 'Server Down Try Again Later'
                                                                                            ])->setStatusCode(403);
                                                                                        }
                                                                                    }
                                                                                    else {
                                                                                        return response()->json([
                                                                                            'status' => 'fail',
                                                                                            'message' => 'Unable To Debit User Account'
                                                                                        ])->setStatusCode(403);
                                                                                    }
                                                                                }
                                                                                else {
                                                                                    return response()->json([
                                                                                        'status' => 'fail',
                                                                                        'message' => 'Insufficient Account Kindly Fund Your ' . ($vending_system == 'wallet' ? '' : $vending_system) . ' Wallet => ₦' . number_format($user_balance, 2)
                                                                                    ])->setStatusCode(403);
                                                                                }
                                                                            }
                                                                            else {
                                                                                return response()->json([
                                                                                    'status' => 'fail',
                                                                                    'message' => 'Insufficient Account Kindly Fund Your ' . ($vending_system == 'wallet' ? '' : $vending_system) . ' Wallet => ₦' . number_format($user_balance, 2)
                                                                                ])->setStatusCode(403);
                                                                            }
                                                                        }
                                                                        else {
                                                                            return response()->json([
                                                                                'status' => 'fail',
                                                                                'message' => 'Invalid Account Balance'
                                                                            ])->setStatusCode(403);
                                                                        }
                                                                    }
                                                                    else {
                                                                        return response()->json([
                                                                            'status' => 'fail',
                                                                            'message' => 'Unable to Detect Amount'
                                                                        ])->setStatusCode(403);
                                                                    }
                                                                }
                                                                else {
                                                                    return response()->json([
                                                                        'status' => 'fail',
                                                                        'message' => 'Unable to Get Wallet Store Balance'
                                                                    ])->setStatusCode(403);
                                                                }
                                                            }
                                                            else {
                                                                return response()->json([
                                                                    'status' => 'fail',
                                                                    'message' => 'Unable to Get Vending Store'
                                                                ])->setStatusCode(403);
                                                            }
                                                        }
                                                        else {
                                                            return response()->json([
                                                                'status' => 'fail',
                                                                'message' => 'Try and Login To Our Website First'
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
                                                        'message' => 'Unable to Bypass Number'
                                                    ])->setStatusCode(403);
                                                }
                                            }
                                            else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => 'Invalid Phone Number ' . $phone
                                                ])->setStatusCode(403);
                                            }
                                        }
                                        else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => $network_d->network . " " . $plan_d->plan_type . " is not available right now"
                                            ])->setStatusCode(403);
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'Unable to detect lock service'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Invalid Data Plan ID and Network'
                                    ])->setStatusCode(403);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Network ID invalid'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Request ID Exits Before'
                            ])->setStatusCode(403);
                        }
                    }
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Phone Number Blocked'
                    ])->setStatusCode(403);
                }

            }
            else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid AccessToken Or Access Denial'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 'fail',
                'message' => 'AccessToken Required'
            ])->setStatusCode(403);
        }
    }
}