<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class BulksmsPurchase extends Controller
{
    public function Buy(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $transid = $this->purchase_ref('BULKSMS_');
        $limit = $this->core()->bulk_length;
        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'message' => "required|min:1|max:$limit",
            'sender' => 'required|min:1|max:10',
        ]);
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $system = config('app.name');
            if ($this->core()->allow_pin == 1) {
                // transaction pin required
                $check = DB::table('user')->where(['id' => $this->verifytoken($request->token), 'pin' => $request->pin]);
                if ($check->count() == 1) {
                    $det = $check->first();
                    $accessToken = $det->apikey;
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
            $user_check = DB::table('user')->where(['apikey' => $accessToken, 'status' => 1]);
            if ($user_check->count() == 1) {
                $user = $user_check->first();
                if (DB::table('bulksms')->where('transid', $transid)->count() == 0 and DB::table('message')->where('transid', $transid)->count() == 0) {
                    if ($this->core()->bulksms == 1) {
                        if ($validator->fails()) {
                            return response()->json([
                                'message' => $validator->errors()->first(),
                                'status' => 'fail'
                            ])->setStatusCode(403);
                        } else {
                            $habukhan_new_go = true;

                            if ($habukhan_new_go == true) {

                                $number = explode(',', $request->number);

                                if (count($number) <= 10000) {
                                    $real_number = [];
                                    $wrong_number = [];
                                    // geting right number and wrong number
                                    for ($a = 0; $a < count($number); $a++) {
                                        $check_number = $number[$a];
                                        if ((substr($check_number, 0, 1) == 0 xor substr($check_number, 0, 3) == 234)) {
                                            $check_habukhan = strlen($check_number);
                                            if ($check_habukhan == 11 xor $check_habukhan == 13) {
                                                if (is_numeric($check_number)) {
                                                    $real_number[] = $check_number;
                                                } else {
                                                    $wrong_number[] = $check_number;
                                                }
                                            } else {
                                                $wrong_number[] = $check_number;
                                            }
                                        } else {
                                            $wrong_number[] = $check_number;
                                        }
                                    }
                                    if ($real_number != null) {
                                        $charges = count($real_number) * $this->core()->bulk_sms;
                                        DB::beginTransaction();
                                        $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();
                                        if ($user->bal > 0) {
                                            if ($user->bal >= $charges) {
                                                $debit = $user->bal - $charges;
                                                $refund = $debit + $charges;
                                                if (DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
                                                    DB::commit();
                                                    $trans_history = [
                                                        'username' => $user->username,
                                                        'amount' => $charges,
                                                        'message' => 'Transaction on process sending messages to ' . count($real_number) . ' phone numbers',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $debit,
                                                        'habukhan_date' => $this->system_date(),
                                                        'plan_status' => 0,
                                                        'transid' => $transid,
                                                        'role' => 'bulksms'
                                                    ];
                                                    $bulk_history = [
                                                        'username' => $user->username,
                                                        'amount' => $charges,
                                                        'newbal' => $debit,
                                                        'oldbal' => $user->bal,
                                                        'plan_date' => $this->system_date(),
                                                        'transid' => $transid,
                                                        'plan_status' => 0,
                                                        'correct_number' => implode(',', $real_number),
                                                        'wrong_number' => implode(',', $wrong_number),
                                                        'total_number' => count($number),
                                                        'total_correct_number' => count($real_number),
                                                        'total_wrong_number' => count($wrong_number),
                                                        'message' => $request->message,
                                                        'sender_name' => $request->sender,
                                                        'numbers' => implode(',', $number)
                                                    ];
                                                    if ($this->inserting_data('message', $trans_history) and $this->inserting_data('bulksms', $bulk_history)) {
                                                        $vedning = DB::table('bulksms_sel')->first();
                                                        $choosed = $vedning->bulksms;
                                                        $habukhanvend = new BulksmsSend();
                                                        $we_chose = array('sender' => $request->sender, 'message' => $request->message, 'number' => $request->number, 'transid' => $transid);
                                                        $response = $habukhanvend::$choosed($we_chose);
                                                        if ($response == 'success') {
                                                            DB::table('bulksms')->where('transid', $transid)->update(['plan_status' => 1]);
                                                            DB::table('message')->where('transid', $transid)->update(['plan_status' => 1, 'message' => 'Transaction Successful']);
                                                            return response()->json([
                                                                'amount' => $charges,
                                                                'newbal' => $debit,
                                                                'oldbal' => $user->bal,
                                                                'transid' => $transid,
                                                                'plan_date' => $this->system_date(),
                                                                'request-id' => $transid,
                                                                'status' => 'success',
                                                                'correct_number' => implode(',', $real_number),
                                                                'wrong_number' => implode(',', $wrong_number),
                                                                'total_number' => count($number),
                                                                'total_correct_number' => count($real_number),
                                                                'total_wrong_number' => count($wrong_number),
                                                                'sms_body' => $request->message,
                                                                'sender_name' => $request->sender,
                                                                'numbers' => implode(',', $number),
                                                                'message' => 'messages sent to ' . count($real_number) . ' phone numbers',
                                                            ]);
                                                        } else if ($response == 'process') {
                                                            return response()->json([
                                                                'amount' => $charges,
                                                                'newbal' => $debit,
                                                                'oldbal' => $user->bal,
                                                                'plan_date' => $this->system_date(),
                                                                'request-id' => $transid,
                                                                'status' => 'process',
                                                                'correct_number' => implode(',', $real_number),
                                                                'wrong_number' => implode(',', $wrong_number),
                                                                'total_number' => count($number),
                                                                'total_correct_number' => count($real_number),
                                                                'total_wrong_number' => count($wrong_number),
                                                                'sms_body' => $request->message,
                                                                'sender_name' => $request->sender,
                                                                'numbers' => implode(',', $number),
                                                                'message' => 'Transaction on process sending messages to ' . count($real_number) . " phone number(s)",
                                                            ]);
                                                        } else if ($response == 'fail') {
                                                            DB::table('user')->where(['username' => $user->username, 'id' => $user->id]);
                                                            DB::table('bulksms')->where('transid', $transid)->update(['plan_status' => 2, 'newbal' => $refund]);
                                                            DB::table('message')->where('transid', $transid)->update(['plan_status' => 2, 'message' => 'Transaction fail', 'newbal' => $refund]);
                                                            return response()->json([
                                                                'amount' => $charges,
                                                                'newbal' => $debit,
                                                                'oldbal' => $user->bal,
                                                                'plan_date' => $this->system_date(),
                                                                'request-id' => $transid,
                                                                'status' => 'fail',
                                                                'correct_number' => implode(',', $real_number),
                                                                'wrong_number' => implode(',', $wrong_number),
                                                                'total_number' => count($number),
                                                                'total_correct_number' => count($real_number),
                                                                'total_wrong_number' => count($wrong_number),
                                                                'sms_body' => $request->message,
                                                                'sender_name' => $request->sender,
                                                                'numbers' => implode(',', $number),
                                                                'message' => 'Transaction Fail',
                                                            ]);
                                                        } else {
                                                            return response()->json([
                                                                'amount' => $charges,
                                                                'newbal' => $debit,
                                                                'oldbal' => $user->bal,
                                                                'plan_date' => $this->system_date(),
                                                                'request-id' => $transid,
                                                                'status' => 'process',
                                                                'correct_number' => implode(',', $real_number),
                                                                'wrong_number' => implode(',', $wrong_number),
                                                                'total_number' => count($number),
                                                                'total_correct_number' => count($real_number),
                                                                'total_wrong_number' => count($wrong_number),
                                                                'sms_body' => $request->message,
                                                                'sender_name' => $request->sender,
                                                                'numbers' => implode(',', $number),
                                                                'message' => 'Transaction on process sending messages to ' . count($real_number) . " phone number(s)",
                                                            ]);
                                                        }
                                                    }
                                                } else {
                                                    return response()->json([
                                                        'status' => 'fail',
                                                        'message' => 'Unable to debit user please try again'
                                                    ])->setStatusCode(403);
                                                }
                                            } else {
                                                return response()->json([
                                                    'status' => 'fail',
                                                    'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->bal, 2)
                                                ])->setStatusCode(403);
                                            }
                                        } else {
                                            return response()->json([
                                                'status' => 'fail',
                                                'message' => 'Insufficient Account Kindly Fund Your Wallet => ₦' . number_format($user->bal, 2)
                                            ])->setStatusCode(403);
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'Invalid Phone Number'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'maximum number is 10,000'
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'You Have reach transaction Limit Kindly Message the admin to upgrade your account'
                                ])->setStatusCode(403);
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'BULKSMS Not Available Now Please Try Again After Some Minutes'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Kindly Retry After Some minutes'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'invalid AccessToken'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Invalid Token'
            ])->setStatusCode(403);
        }
    }
}
