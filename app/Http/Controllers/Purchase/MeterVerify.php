<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class  MeterVerify extends Controller
{
    public function Check(Request $request)
    {
        if (!empty($request->disco)) {
            if (!empty($request->meter_number)) {
                if (!empty($request->meter_type)) {
                    if ((strtolower($request->meter_type) == 'prepaid') xor strtolower($request->meter_type) == 'postpaid') {
                        if (DB::table('bill_plan')->where('plan_id', $request->disco)->count() == 1) {
                            $bill_sel = DB::table('bill_sel')->first();
                            $vend_from = $bill_sel->bill;
                            $verify_meter = new MeterSend();
                            $data = [
                                'disco' => $request->disco,
                                'meter_number' => $request->meter_number,
                                'meter_type' => strtolower($request->meter_type)
                            ];
                            $response = $verify_meter->$vend_from($data);
                            if (!empty($response)) {
                                return response()->json([
                                    'status' => 'success',
                                    'name' => $response
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 'fail',
                                    'message' => 'invalid Meter Number'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Invalid Disco Id'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Invalid meter type'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Meter type Required'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Meter Number Required'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Disco ID Required'
            ])->setStatusCode(403);
        }
    }
}
