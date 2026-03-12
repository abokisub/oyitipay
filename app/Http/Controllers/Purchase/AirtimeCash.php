<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class AirtimeCash extends Controller
{

    public function Convert(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $transid = $this->purchase_ref('AIRTIME2CASH_');
        $validator = Validator::make($request->all(), [
            'network' => 'required',
            'sender_number' => 'required|numeric|digits:11',
            'bypass' => 'required',
            'payment_type' => 'required',
            'amount' => 'required|numeric|integer|not_in:0|gt:0',
        ]);
        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $system = "APP";

            $verified_id = $this->verifyapptoken($request->user_id);
            $check = DB::table('user')->where(['id' => $verified_id, 'status' => 1]);
            if ($check->count() == 1) {
                $d_token = $check->first();
                if (trim($d_token->pin) == trim($request->pin)) {
                    $accessToken = $d_token->apikey;
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            } else {
                $accessToken = 'null';
            }
        } else if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $system = config('app.name');

            if ($this->core()->allow_pin == 1) {
                // transaction pin required
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    if (trim($det->pin) == trim($request->pin)) {
                        $accessToken = $det->apikey;
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Invalid Transaction Pin'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Transaction Pin'
                    ])->setStatusCode(403);
                }
            } else {
                // transaction pin not required
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token)]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    $accessToken = $det->apikey;
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'An Error Occur'
                    ])->setStatusCode(403);
                }
            }
        } else {
            $system = "API";
            $d_token = $request->header('Authorization');
            $accessToken = trim(str_replace("Token", "", $d_token));
        }

        if ($accessToken) {
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 'fail'
                ])->setStatusCode(403);
            } else {
                $user_check = DB::table('user')->where(['apikey' => $accessToken, 'status' => 1]);
                if ($user_check->count() == 1) {
                    $user = $user_check->first();
                    if (DB::table('block')->where(['number' => $request->sender_number])->count() == 0) {
                        if (DB::table('cash')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                            $phone = $request->sender_number;
                            // check network lock
                            $net_lock = DB::table('network')->where('network', $request->network)->first();
                            if (!$net_lock || $net_lock->cash != 1) {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Airtime conversion is currently locked for ' . $request->network
                                ])->setStatusCode(403);
                            }

                            // check number
                            if ($request->bypass == false || $request->bypass == 'false') {
                                $validate = substr($phone, 0, 4);
                                if ($request->network == "MTN") {
                                    if (strpos(" 0702 0703 0713 0704 0706 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validate) == FALSE || strlen($phone) != 11) {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'This is not a MTN Number => ' . $phone
                                        ])->setStatusCode(403);
                                    } else {
                                        $habukhan_bypass = true;
                                    }
                                } else if ($request->network == "GLO") {
                                    if (strpos(" 0805 0705 0905 0807 0907 0707 0817 0917 0717 0715 0815 0915 0811 0711 0911 ", $validate) == FALSE || strlen($phone) != 11) {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'This is not a GLO Number =>' . $phone
                                        ])->setStatusCode(403);
                                    } else {
                                        $habukhan_bypass = true;
                                    }
                                } else if ($request->network == "AIRTEL") {
                                    if (strpos(" 0904 0802 0902 0702 0808 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validate) == FALSE || strlen($phone) != 11) {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'This is not a AIRTEL Number => ' . $phone
                                        ])->setStatusCode(403);
                                    } else {
                                        $habukhan_bypass = true;
                                    }
                                } else if ($request->network == "9MOBILE") {
                                    if (strpos(" 0809 0909 0709 0819 0919 0719 0817 0917 0717 0718 0918 0818 0808 0708 0908 ", $validate) == FALSE || strlen($phone) != 11) {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'This is not a 9MOBILE Number => ' . $phone
                                        ])->setStatusCode(403);
                                    } else {
                                        $habukhan_bypass = true;
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Unable to get Network Name'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                $habukhan_bypass = true;
                            }
                            //check if phone number is validated
                            if (substr($phone, 0, 1) == 0) {
                                // if bypassed
                                if ($habukhan_bypass == true) {
                                    $habukhan_new_go = true;
                                    if ($habukhan_new_go) {
                                        if ((strtolower($request->payment_type) == 'wallet') xor strtolower($request->payment_type) == 'bank') {
                                            if ($request->network == '9MOBILE') {
                                                $network_name = 'mobile';
                                            } else {
                                                $network_name = strtolower($request->network);
                                            }
                                            if (DB::table('user_bank')->where('username', $user->username)->count() > 0 || $user->type != 'WITHDRAW') {
                                                $system_admin = DB::table('cash_discount')->first();
                                                $credit = ($request->amount / 100) * $system_admin->$network_name;
                                                $trans_history = [
                                                    'username' => $user->username,
                                                    'amount' => $credit,
                                                    'message' => 'Airtime to cash on process',
                                                    'phone_account' => $phone,
                                                    'oldbal' => $user->bal,
                                                    'newbal' => $user->bal,
                                                    'habukhan_date' => $this->system_date(),
                                                    'plan_status' => 0,
                                                    'transid' => $transid,
                                                    'role' => 'cash'
                                                ];
                                                $trans_cash = [
                                                    'username' => $user->username,
                                                    'amount' => $request->amount,
                                                    'amount_credit' => $credit,
                                                    'newbal' => $user->bal,
                                                    'oldbal' => $user->bal,
                                                    'transid' => $transid,
                                                    'network' => $request->network,
                                                    'payment_type' => strtoupper($request->payment_type),
                                                    'plan_status' => 0,
                                                    'plan_date' => $this->system_date(),
                                                    'system' => $system,
                                                    'sender_number' => $request->sender_number
                                                ];
                                                if ($this->inserting_data('message', $trans_history) and $this->inserting_data('cash', $trans_cash)) {

                                                    $send_message = $user->username . " want to convert " . $request->network . " ₦" . number_format($request->amount, 2) . " to cash. payment method is (" . strtoupper($request->payment_type) . "), Amount to Be Credited is ₦" . number_format($credit, 2) . " Airtime sent from " . $request->sender_number . " Reference is => " . $transid;
                                                    $mes_data = [
                                                        'mes' => $send_message,
                                                        'title' => 'AIRTIME 2 CASH'
                                                    ];
                                                    ApiSending::ADMINEMAIL($mes_data);
                                                    DB::table('request')->insert(['username' => $user->username, 'message' => $send_message, 'date' => $this->system_date(), 'transid' => $transid, 'status' => 0, 'title' => 'AIRTIME 2 CASH']);
                                                    return response()->json([
                                                        'status' => 'success',
                                                        'message' => 'Transaction On Process',
                                                        'request-id' => $transid,
                                                        'transid' => $transid,
                                                        'amount_credited' => $credit
                                                    ]);
                                                } else {
                                                    DB::table('message')->where(['transid' => $transid, 'username' => $user->username])->delete();
                                                    DB::table('cash')->where(['transid' => $transid, 'username' => $user->username])->delete();
                                                    return response()->json([
                                                        'status' => 'fail',
                                                        'message' => 'unable to insert infomation'
                                                    ])->setStatusCode(403);
                                                }
                                            } else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => 'Add Your Account Number (Kindly check settings)'
                                                ])->setStatusCode(403);
                                            }
                                        } else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'payment type unknown'
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'You  have reach Daily Transaction Limit Kindly message the admin for upgrade'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Unable to bypass system account'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'invalid phone number => ' . $phone
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Kindly Retry again'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Number Block'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Access Token'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Access Token Required'
            ])->setStatusCode(403);
        }
    }

    public function A2C_SendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'network' => 'required',
            'sender_number' => 'required|numeric|digits:11',
            'amount' => 'required|numeric|integer|not_in:0|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 'fail'], 403);
        }

        $network = DB::table('network')->where('network', $request->network)->first();
        if (!$network || $network->cash != 1) {
            return response()->json(['status' => 'fail', 'message' => 'Airtime conversion is currently unavailable for this network'], 400);
        }

        // Autopilot A2C API strictly requires 11-digit local format starting with 0
        $phone = $request->sender_number;

        $payload = [
            'network' => (string) $network->autopilot_id,
            'networkName' => strtoupper($network->network),
            'senderNumber' => $phone,
            'amount' => (string) $request->amount
        ];

        $response = $this->autopilot_request('/v1/send-resend/auto-airtime-to-cash-otp', $payload);

        if (isset($response['status']) && $response['status'] == true) {
            return response()->json([
                'status' => 'success',
                'identifier' => $response['data']['identifier'] ?? $response['data']['sessionId'] ?? '',
                'message' => $response['data']['message'] ?? 'OTP sent successfully'
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'message' => $response['message'] ?? 'Unable to send OTP'
        ], 400);
    }

    public function A2C_VerifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 'fail'], 403);
        }

        $payload = [
            'identifier' => $request->identifier,
            'otp' => $request->otp
        ];

        $response = $this->autopilot_request('/v1/verify/auto-airtime-to-cash-otp', $payload);

        if (isset($response['status']) && $response['status'] == true) {
            return response()->json([
                'status' => 'success',
                'sessionId' => $response['data']['sessionId'],
                'balance' => $response['data']['airtimeBalance'] ?? 'N/A',
                'message' => $response['data']['message'] ?? 'OTP verified successfully'
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'message' => $response['message'] ?? 'OTP verification failed'
        ], 400);
    }

    public function A2C_Execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required',
            'transid' => 'required', // The app's reference for the conversion
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 'fail'], 403);
        }

        $reference = $this->generateAutopilotReference();

        // Retrieve network info from the cash transaction
        $cash = DB::table('cash')->where('transid', $request->transid)->first();
        if (!$cash) {
            return response()->json(['status' => 'fail', 'message' => 'Transaction not found'], 400);
        }
        $network = DB::table('network')->where('network', $cash->network)->first();
        if (!$network || $network->cash != 1) {
            return response()->json(['status' => 'fail', 'message' => 'Airtime conversion is currently unavailable for this network'], 400);
        }

        // Autopilot A2C API strictly requires 11-digit local format starting with 0
        $phone = $cash->sender_number;

        $payload = [
            'network' => (string) $network->autopilot_id,
            'amount' => (string) $cash->amount,
            'quantity' => "1",
            'pin' => $request->transferPin, // API expects 'pin' for share and sell pin
            'sessionId' => $request->sessionId,
            'reference' => $reference
        ];

        DB::table('cash')->where('transid', $request->transid)->update(['api_reference' => $reference]);

        $response = $this->autopilot_request('/v1/send-airtime/auto-airtime-to-cash', $payload);

        if (isset($response['status']) && $response['status'] == true) {
            return response()->json([
                'status' => 'success',
                'message' => $response['data']['message'] ?? 'Airtime conversion initiated successfully'
            ]);
        }

        // Extensive Logging for debugging "Random Errors"
        \Log::info("A2C Execute Response (" . $request->transid . "): " . json_encode($response));

        $msg = $response['data']['message'] ?? $response['message'] ?? 'Airtime conversion failed';

        // Map provider errors to user-friendly messages
        if (str_contains(strtolower($msg), 'pin') || str_contains(strtolower($msg), 'unauthorized') || str_contains(strtolower($msg), 'authentication failed')) {
            $msg = 'Incorrect Share & Sell PIN. Please check and try again.';
        } elseif (str_contains(strtolower($msg), 'insufficient')) {
            $msg = 'Insufficient airtime balance on the provided number.';
        }

        return response()->json([
            'status' => 'fail',
            'message' => $msg
        ], 400);
    }
}
