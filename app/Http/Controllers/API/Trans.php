<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Trans extends Controller
{

    public function UserTrans(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);
                $database_name = strtolower($request->database_name);
                if ($database_name === 'bank_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('bank_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('bank_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bank_trans' => DB::table('bank_transfer')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else if ($database_name == 'cable_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%")->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                elseif ($database_name == 'bill_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%")->orWhere('customer_name', 'LIKE', "%$search%")->orWhere('token', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {

                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else if ($database_name == 'bulksms_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('correct_number', 'LIKE', "%$search%")->orWhere('wrong_number', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('total_correct_number', 'LIKE', "%$search%")->orWhere('total_wrong_number', 'LIKE', "%$search%")->orWhere('message', 'LIKE', "%$search%")->orWhere('sender_name', 'LIKE', "%$search%")->orWhere('numbers', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'bulksms_trans' => DB::table('bulksms')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else if ($database_name == 'cash_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where('username', $user->username)->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('amount_credit', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('payment_type', 'LIKE', "%$search%")->orWhere('network', 'LIKE', "%$search%")->orWhere('sender_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'cash_trans' => DB::table('cash')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else if ($database_name == 'result_trans') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('purchase_code', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else if ($database_name == 'transfers') {
                    if (!empty($search)) {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'transfers' => DB::table('transfers')->where(['user_id' => $user->id])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('reference', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%")->orWhere('status', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'transfers' => DB::table('transfers')->where(['user_id' => $user->id, 'status' => $request->status])->Where(function ($query) use ($search) {
                                $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('reference', 'LIKE', "%$search%")->orWhere('account_number', 'LIKE', "%$search%")->orWhere('account_name', 'LIKE', "%$search%")->orWhere('bank_code', 'LIKE', "%$search%")->orWhere('status', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                    else {
                        if ($request->status == 'ALL') {
                            return response()->json([
                                'transfers' => DB::table('transfers')->where(['user_id' => $user->id])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                        else {
                            return response()->json([
                                'transfers' => DB::table('transfers')->where(['user_id' => $user->id, 'status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                            ]);
                        }
                    }
                }
                else {
                    return response()->json([
                        'message' => 'Not invalid'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function TransferDetails(Request $request)
    {
        if (DB::table('transfers')->where(['reference' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('transfers')->where(['reference' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function AllDepositHistory(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);
                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'deposit_trans' => DB::table('deposit')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                            $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('wallet_type', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%")->orWhere('credit_by', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('monify_ref', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit),
                        ]);
                    }
                    else {
                        return response()->json([
                            'deposit_trans' => DB::table('deposit')->where(['username' => $user->username, 'status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('wallet_type', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%")->orWhere('credit_by', 'LIKE', "%$search%")->orWhere('charges', 'LIKE', "%$search%")->orWhere('monify_ref', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'deposit_trans' => DB::table('deposit')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit),
                        ]);
                    }
                    else {
                        return response()->json([
                            'deposit_trans' => DB::table('deposit')->where(['username' => $user->username, 'status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                if (!$request->has('status') || $request->status == 'null' || empty($request->status)) {
                    $request->merge(['status' => 'ALL']);
                }
                $search = strtolower($request->search);
                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'all_summary' => DB::table('message')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                            $query->orWhere('message', 'LIKE', "%$search%")->orWhere('habukhan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%");
                        })->select('message', 'amount', 'oldbal', 'newbal', 'habukhan_date as Habukhan_date', 'habukhan_date as adex_date', 'transid', 'plan_status', 'role')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'all_summary' => DB::table('message')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('message', 'LIKE', "%$search%")->orWhere('habukhan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%");
                        })->select('message', 'amount', 'oldbal', 'newbal', 'habukhan_date as Habukhan_date', 'habukhan_date as adex_date', 'transid', 'plan_status', 'role')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'all_summary' => DB::table('message')->where(['username' => $user->username])->select('message', 'amount', 'oldbal', 'newbal', 'habukhan_date as Habukhan_date', 'habukhan_date as adex_date', 'transid', 'plan_status', 'role')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'all_summary' => DB::table('message')->where(['username' => $user->username, 'plan_status' => $request->status])->select('message', 'amount', 'oldbal', 'newbal', 'habukhan_date as Habukhan_date', 'habukhan_date as adex_date', 'transid', 'plan_status', 'role')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }
    public function AllAirtimeUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);
                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username])->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('discount', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'airtime_trans' => DB::table('airtime')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }
    public function AllStockHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->where('wallet', '!=', 'wallet')->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->where('wallet', '!=', 'wallet')->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('wallet', '!=', 'wallet')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllDataHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('network', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('plan_phone', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('network_type', 'LIKE', "%$search%")->orWhere('wallet', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'data_trans' => DB::table('data')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'data_trans' => DB::table('data')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllCableHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where('username', $user->username)->Where(function ($query) use ($search) {
                            $query->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('cable_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('iuc', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('cable_plan', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'cable_trans' => DB::table('cable')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllBillHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where('username', $user->username)->Where(function ($query) use ($search) {
                            $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('disco_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('meter_number', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('meter_type', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'bill_trans' => DB::table('bill')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function AllResultHistoryUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->count() == 1) {
                $user = DB::table('user')->where(['id' => $this->verifytoken($request->id), 'status' => 1])->first();
                $search = strtolower($request->search);

                if (!empty($search)) {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where('username', $user->username)->Where(function ($query) use ($search) {
                            $query->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->Where(function ($query) use ($search) {
                            $query->orWhere('exam_name', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('oldbal', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('api_response', 'LIKE', "%$search%")->orWhere('quantity', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
                else {
                    if ($request->status == 'ALL') {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where('username', $user->username)->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                    else {
                        return response()->json([
                            'result_trans' => DB::table('exam')->where(['username' => $user->username, 'plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                        ]);
                    }
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access Denail'
                ])->setStatusCode(403);
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Access Denail'
            ])->setStatusCode(403);
        }
    }

    public function DataTrans(Request $request)
    {
        if (DB::table('data')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function AirtimeTrans(Request $request)
    {
        if (DB::table('airtime')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('airtime')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function DepositTrans(Request $request)
    {
        if (DB::table('deposit')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('deposit')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function CableTrans(Request $request)
    {
        if (DB::table('cable')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('cable')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function BillTrans(Request $request)
    {
        if (DB::table('bill')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bill')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function AirtimeCashTrans(Request $request)
    {
        if (DB::table('cash')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('cash')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function BulkSMSTrans(Request $request)
    {
        if (DB::table('bulksms')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bulksms')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function ResultCheckerTrans(Request $request)
    {
        if (DB::table('exam')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('exam')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
    public function ManualTransfer(Request $request)
    {
        if (DB::table('bank_transfer')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('bank_transfer')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function DataCardInvoice(Request $request)
    {
        if (DB::table('data_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data_card')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function DataCardSuccess(Request $request)
    {
        if (DB::table('data_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('data_card')->where(['transid' => $request->id])->first(),
                'card_map' => DB::table('dump_data_card_pin')->where(['transid' => $request->id])->get()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function DataRechardPrint(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $user = $check_user->first();
                    $search = strtolower($request->search);
                    $database_name = strtolower($request->database_name);
                    if ($database_name == 'data_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                    $query->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                            else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                    $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('plan_type', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                        else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                            else {
                                return response()->json([
                                    'data_card' => DB::table('data_card')->where(['username' => $user->username])->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    }
                    else if ($database_name == 'recharge_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                    $query->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                            else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(function ($query) use ($search) {
                                    $query->orWhere('username', 'LIKE', "%$search%")->orWhere('plan_date', 'LIKE', "%$search%")->orWhere('load_pin', 'LIKE', "%$search%")->orWhere('transid', 'LIKE', "%$search%")->orWhere('newbal', 'LIKE', "%$search%")->orWhere('system', 'LIKE', "%$search%")->orWhere('card_name', 'LIKE', "%$search%")->orWhere('plan_name', 'LIKE', "%$search%");
                                })->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                        else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                            else {
                                return response()->json([
                                    'recharge_card' => DB::table('recharge_card')->where(['username' => $user->username])->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->limit)
                                ]);
                            }
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Not Found'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function RechargeCardProcess(Request $request)
    {
        if (DB::table('recharge_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('recharge_card')->where(['transid' => $request->id])->first()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }

    public function RechargeCardPrint(Request $request)
    {
        if (DB::table('recharge_card')->where(['transid' => $request->id])->count() == 1) {
            return response()->json([
                'trans' => DB::table('recharge_card')->where(['transid' => $request->id])->first(),
                'card_map' => DB::table('dump_recharge_card_pin')->where(['transid' => $request->id])->get()
            ]);
        }
        else {
            return response()->json([
                'message' => 'Not Available'
            ])->setStatusCode(403);
        }
    }
}