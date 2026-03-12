<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ReceiptService;


class ExamPurchase extends Controller
{

    public function ExamPurchase(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $validator = Validator::make($request->all(), [
            'exam' => 'required',
            'quantity' => 'required|numeric|integer|not_in:0|gt:0|min:1|max:5',
        ]);
        // Professional Refactor: Use client-provided request-id for idempotency if available
        if ($request->has('request-id')) {
            $transid = $request->input('request-id');
        } else {
            $transid = $this->purchase_ref('RESULTCHECKER_');
        }
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
                $user_check = DB::table('user')->where(function ($query) use ($accessToken) {
                    $query->where('apikey', $accessToken)
                        ->orWhere('app_key', $accessToken)
                        ->orWhere('habukhan_key', $accessToken);
                })->where('status', 1);
                if ($user_check->count() == 1) {
                    $user = $user_check->first();
                    // check exam id
                    if (DB::table('exam_id')->where('plan_id', $request->exam)->count() == 1) {
                        $exam = DB::table('exam_id')->where('plan_id', $request->exam)->first();
                        $exam_name = strtolower($exam->exam_name);
                        $exam_lock = DB::table('cable_result_lock')->first();

                        // Null Safety for System Configuration
                        if (!$exam_lock || !isset($exam_lock->$exam_name) || $exam_lock->$exam_name != 1) {
                            return response()->json([
                                'status' => 'fail',
                                'message' => strtoupper($exam_name) . ' Not Available Right Now'
                            ])->setStatusCode(403);
                        }

                        $result_price = DB::table('result_charge')->first();

                        if (!$result_price || !isset($result_price->$exam_name)) {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Price configuration missing for ' . strtoupper($exam_name)
                            ])->setStatusCode(403);
                        }

                        $exam_price = $result_price->$exam_name * $request->quantity;

                        if (DB::table('exam')->where('transid', $transid)->count() == 0 && DB::table('message')->where('transid', $transid)->count() == 0) {
                            DB::beginTransaction();
                            $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();
                            if ($user->bal > 0) {
                                if ($user->bal >= $exam_price) {
                                    $debit = $user->bal - $exam_price;
                                    $refund = $debit + $exam_price;
                                    $habukhan_new_go = true;

                                    if ($habukhan_new_go == true) {
                                        if (DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
                                            DB::commit();
                                            $trans_history = [
                                                'username' => $user->username,
                                                'amount' => $exam_price,
                                                'message' => '⏳ Processing ' . strtoupper($exam_name) . ' Exam PIN (' . $request->quantity . ' units)...',
                                                'oldbal' => $user->bal,
                                                'newbal' => $debit,
                                                'habukhan_date' => $this->system_date(),
                                                'plan_status' => 0,
                                                'transid' => $transid,
                                                'role' => 'exam',
                                                'service_type' => 'EDU_PIN',
                                                'transaction_channel' => 'EXTERNAL'
                                            ];
                                            $exam_history = [
                                                'username' => $user->username,
                                                'amount' => $exam_price,
                                                'plan_status' => 0,
                                                'plan_date' => $this->system_date(),
                                                'transid' => $transid,
                                                'exam_name' => strtoupper($exam_name),
                                                'oldbal' => $user->bal,
                                                'newbal' => $debit,
                                                'quantity' => $request->quantity,
                                                'purchase_code' => null,
                                            ];
                                            if (DB::table('exam')->insert($exam_history) and DB::table('message')->insert($trans_history)) {
                                                $exam_sel = DB::table('exam_sel')->first();
                                                $exam_vend = $exam_sel->$exam_name;
                                                $send_data = [
                                                    'transid' => $transid,
                                                    'username' => $user->username
                                                ];
                                                $response = ExamSend::$exam_vend($send_data);
                                                if ($response == 'success') {
                                                    $sendbank = DB::table('exam')->where('transid', $transid)->first();
                                                    $receiptService = new ReceiptService();
                                                    $successMessage = $receiptService->getFullMessage('EDU_PIN', [
                                                        'exam' => strtoupper($exam_name),
                                                        'pin' => $sendbank->purchase_code ?? 'See History',
                                                        'serial' => 'N/A',
                                                        'amount' => $exam_price,
                                                        'reference' => $transid,
                                                        'status' => 'SUCCESS'
                                                    ]);

                                                    DB::table('exam')->where('transid', $transid)->update(['plan_status' => 1]);
                                                    DB::table('message')->where('transid', $transid)->update(['plan_status' => 1, 'message' => $successMessage]);
                                                    $sendbank = DB::table('exam')->where('transid', $transid)->first();

                                                    // SEND NOTIFICATION
                                                    try {
                                                        (new \App\Services\NotificationService())->sendExamPinNotification(
                                                            $user,
                                                            $exam_price,
                                                            strtoupper($exam_name),
                                                            $request->quantity,
                                                            $transid
                                                        );
                                                    } catch (\Exception $e) {
                                                        \Log::error("Exam PIN Notification Error: " . $e->getMessage());
                                                    }

                                                    return response()->json([
                                                        'username' => $user->username,
                                                        'amount' => $exam_price,
                                                        'transid' => $transid,
                                                        'quantity' => $request->quantity,
                                                        'message' => strtoupper($exam_name) . ' Exam Pin Generated',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $refund,
                                                        'date' => $this->system_date(),
                                                        'status' => 'success',
                                                        'request-id' => $transid,
                                                        'pin' => $sendbank->purchase_code
                                                    ]);
                                                } else if ($response == 'process') {
                                                    return response()->json([
                                                        'username' => $user->username,
                                                        'amount' => $exam_price,
                                                        'quantity' => $request->quantity,
                                                        'message' => strtoupper($exam_name) . ' Exam Pin Is On Process',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $debit,
                                                        'date' => $this->system_date(),
                                                        'status' => 'process',
                                                        'request-id' => $transid,
                                                    ]);
                                                } else if ($response == 'fail') {
                                                    $failMessage = "❌ Exam PIN Purchase Failed\n\nYou attempted to purchase " . strtoupper($exam_name) . " but the transaction failed. Your wallet has been refunded.";

                                                    DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $refund]);
                                                    DB::table('exam')->where('transid', $transid)->update(['plan_status' => 2, 'newbal' => $refund]);
                                                    DB::table('message')->where('transid', $transid)->update(['plan_status' => 2, 'newbal' => $refund, 'message' => $failMessage]);
                                                    return response()->json([
                                                        'username' => $user->username,
                                                        'amount' => $exam_price,
                                                        'quantity' => $request->quantity,
                                                        'message' => 'Transaction Fail',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $refund,
                                                        'date' => $this->system_date(),
                                                        'status' => 'fail',
                                                        'request-id' => $transid,
                                                    ]);
                                                } else {
                                                    return response()->json([
                                                        'username' => $user->username,
                                                        'amount' => $exam_price,
                                                        'quantity' => $request->quantity,
                                                        'message' => strtoupper($exam_name) . ' Exam Pin Is On Process',
                                                        'oldbal' => $user->bal,
                                                        'newbal' => $debit,
                                                        'date' => $this->system_date(),
                                                        'status' => 'process',
                                                        'request-id' => $transid,
                                                    ]);
                                                }
                                            }
                                        }
                                    } else {
                                        return response()->json([
                                            'status' => 'fail',
                                            'message' => 'You have Reach Daily Transaction Limit Kindly Message the Admin To Upgrade Your Account'
                                        ])->setStatusCode(403);
                                    }
                                } else {
                                    return response()->json([
                                        'status' => 'fail',
                                        'message' => 'Insufficient Account Kindly fund your wallet => ₦' . number_format($user->bal, 2)
                                    ])->setStatusCode(403);
                                }
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'Insufficient Account Kindly fund your wallet => ₦' . number_format($user->bal, 2)
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Referrence ID Used'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Invalid Exam ID'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Invalid Authorization Token'
                    ])->setStatusCode(403);
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Authorization Token Required'
            ])->setStatusCode(403);
        }
    }
}