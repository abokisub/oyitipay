<?php

namespace App\Http\Controllers\Purchase;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ReceiptService;


class RechargeCard extends Controller
{

    public function RechargeCardPurchase(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $validator = Validator::make($request->all(), [
            'network' => 'required',
            'quantity' => 'required|numeric|integer|not_in:0|gt:0|min:1|max:100',
            'card_name' => 'required|max:200',
            'plan_type' => 'required',
        ]);
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $system = "APP";
            // Professional Refactor: Use client-provided request-id for idempotency if available
            if ($request->has('request-id')) {
                $transid = $request->input('request-id');
            }
            else {
                $transid = $this->purchase_ref('Recharge_card_');
            }

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('user')->where(['id' => $verified_id, 'status' => 1]);
            if ($check->count() == 1) {
                $d_token = $check->first();
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->apikey;
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
            $system = config('app.name');
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
            $system = "API";
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }

        if (!empty($accessToken)) {
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 'fail'
                ])->setStatusCode(403);
            }
            else {
                $user_check = DB::table('user')->where(function ($query) use ($accessToken) {
                    $query->where('apikey', $accessToken)
                        ->orWhere('app_key', $accessToken)
                        ->orWhere('habukhan_key', $accessToken);
                })->where('status', 1);
                if ($user_check->count() == 1) {
                    $user = $user_check->first();
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
                    if (DB::table('network')->where('plan_id', $request->network)->count() == 1) {
                        $network = DB::table('network')->where('plan_id', $request->network)->first();
                        if ($network->recharge_card == 1) {
                            if (DB::table('recharge_card_plan')->where(['network' => $network->network, 'plan_id' => $request->plan_type, 'plan_status' => 1])->count() == 1) {
                                // user limit
                                $recharge_card_plan = DB::table('recharge_card_plan')->where(['network' => $network->network, 'plan_id' => $request->plan_type])->first();
                                $limit_check_passed = true;
                                if ($limit_check_passed == true) {
                                    $recharge_card_price = $recharge_card_plan->$user_type * $request->quantity;
                                    if (DB::table('recharge_card')->where('transid', $transid)->count() == 0 && DB::table('message')->where('transid', $transid)->count() == 0) {
                                        DB::beginTransaction();
                                        $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();
                                        if ($user->bal > 0) {
                                            if ($user->bal >= $recharge_card_price) {
                                                $debit = $user->bal - $recharge_card_price;
                                                $refund = $debit + $recharge_card_price;
                                                if (DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
                                                    DB::commit();
                                                    $trans_history = [
                                                        'username' => $user->username,
                                                        'amount' => $recharge_card_price,
                                                        'message' => '⏳ Processing ' . $network->network . ' Recharge Card printing (' . $request->quantity . ' units)...',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $debit,
                                                        'habukhan_date' => $this->system_date(),
                                                        'plan_status' => 0,
                                                        'transid' => $transid,
                                                        'role' => 'recharge_card',
                                                        'service_type' => 'RECHARGE_CARD',
                                                        'transaction_channel' => 'EXTERNAL'
                                                    ];

                                                    $recharge_card_trans = [
                                                        'username' => $user->username,
                                                        'network' => $network->network,
                                                        'plan_name' => $recharge_card_plan->name,
                                                        'load_pin' => $recharge_card_plan->load_pin,
                                                        'amount' => $recharge_card_price,
                                                        'plan_date' => $this->system_date(),
                                                        'transid' => $transid,
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $debit,
                                                        'plan_status' => 0,
                                                        'system' => $system,
                                                        'quantity' => $request->quantity,
                                                        'card_name' => $request->card_name,

                                                        'check_balance' => $recharge_card_plan->check_balance
                                                    ];
                                                    if (DB::table('recharge_card')->insert($recharge_card_trans) and DB::table('message')->insert($trans_history)) {
                                                        $sending_data = [
                                                            'purchase_plan' => $recharge_card_plan->plan_id,
                                                            'transid' => $transid,
                                                            'username' => $user->username
                                                        ];
                                                        if ($network->network == '9MOBILE') {
                                                            $vending = 'mobile';
                                                        }
                                                        else {
                                                            $vending = strtolower($network->network);
                                                        }
                                                        $sender = new RechargeCardSend();
                                                        $data_sel = DB::table('recharge_card_sel')->first();
                                                        $check_now = $data_sel->$vending;
                                                        $response = $sender->$check_now($sending_data);
                                                        if ($response) {
                                                            if ($response == 'success') {
                                                                // get the pin and serial number here
                                                                $stock_pin = DB::table('dump_recharge_card_pin')->where(['network' => $network->network, 'username' => $user->username, 'transid' => $transid])->get();
                                                                $sold_pin = null;
                                                                $sold_serial = null;
                                                                foreach ($stock_pin as $real_pin) {
                                                                    $sold_pin[] = $real_pin->pin;
                                                                    $sold_serial[] = $real_pin->serial;
                                                                }

                                                                $receiptService = new ReceiptService();
                                                                $successMessage = $receiptService->getFullMessage('RECHARGE_CARD', [
                                                                    'network' => $network->network,
                                                                    'quantity' => $request->quantity,
                                                                    'amount' => $recharge_card_price,
                                                                    'reference' => $transid,
                                                                    'status' => 'SUCCESS'
                                                                ]);

                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => $successMessage]);
                                                                DB::table('recharge_card')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);

                                                                // SEND NOTIFICATION
                                                                try {
                                                                    (new \App\Services\NotificationService())->sendRechargeCardNotification(
                                                                        $user,
                                                                        $recharge_card_price,
                                                                        $network->network,
                                                                        $request->quantity,
                                                                        $transid
                                                                    );
                                                                }
                                                                catch (\Exception $e) {
                                                                    \Log::error("Recharge Card Notification Error: " . $e->getMessage());
                                                                }

                                                                return response()->json([
                                                                    'network' => $network->network,
                                                                    'request-id' => $transid,
                                                                    'transid' => $transid,
                                                                    'amount' => $recharge_card_price,
                                                                    'quantity' => $request->quantity,
                                                                    'status' => 'success',
                                                                    'message' => $network->network . ' Recharge Card Printing Successful',
                                                                    'card_name' => $request->card_name,
                                                                    'oldbal' => $user->bal,
                                                                    'newbal' => $debit,
                                                                    'system' => $system,
                                                                    'serial' => implode(',', $sold_serial),
                                                                    'pin' => implode(',', $sold_pin),
                                                                    'load_pin' => $recharge_card_plan->load_pin,
                                                                    'check_balance' => $recharge_card_plan->check_balance
                                                                ]);
                                                            }
                                                            else {
                                                                // transaction fail
                                                                $failMessage = "❌ Recharge Card Printing Failed\n\nYou attempted to print " . $network->network . " Recharge Cards but the transaction failed. Your wallet has been refunded.";

                                                                DB::table('user')->where('id', $user->id)->update(['bal' => $refund]);
                                                                // trans history
                                                                DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user->bal, 'newbal' => $refund, 'message' => $failMessage]);
                                                                DB::table('recharge_card')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'oldbal' => $user->bal, 'newbal' => $refund]);
                                                                return response()->json([
                                                                    'network' => $network->network,
                                                                    'request-id' => $transid,
                                                                    'amount' => $recharge_card_price,
                                                                    'quantity' => $request->quantity,
                                                                    'status' => 'fail',
                                                                    'message' => $network->network . ' Recharge Card Printing Fail ',
                                                                    'card_name' => $request->card_name,
                                                                    'oldbal' => $user->bal,
                                                                    'newbal' => $debit,
                                                                    'system' => $system,
                                                                ]);
                                                            }
                                                        }
                                                        else {
                                                            return response()->json([
                                                                'network' => $network->network,
                                                                'request-id' => $transid,
                                                                'amount' => $recharge_card_price,
                                                                'quantity' => $request->quantity,
                                                                'status' => 'process',
                                                                'message' => $network->network . ' Recharge Card Printing On Process Quantity is ' . $request->quantity,
                                                                'card_name' => $request->card_name,
                                                                'oldbal' => $user->bal,
                                                                'newbal' => $debit,
                                                                'system' => $system,
                                                            ]);
                                                        }
                                                    }
                                                }
                                            }
                                            else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => 'Insufficient Account Kindly fund your wallet => ₦' . number_format($user->bal, 2)
                                                ])->setStatusCode(403);
                                            }
                                        }
                                        else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'Insufficient Account Kindly fund your wallet => ₦' . number_format($user->bal, 2)
                                            ])->setStatusCode(403);
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'please try again later'
                                        ]);
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
                                    'message' => 'Invalid ' . $network->network . ' Recharge Card Plan Type'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => $network->network . 'Recharge Card Not Avalaible Now'
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
                        'message' => 'Invalid Authorization Token'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Authorization Access Token Required'
            ])->setStatusCode(403);
        }
    }
}