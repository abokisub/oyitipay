<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class IUCvad extends Controller
{
    public function IUC(Request $request)
    {
        if ((isset($request->iuc)) and (!empty($request->iuc))) {
            if ((isset($request->cable)) and (!empty($request->cable))) {
                if (DB::table('cable_id')->where('plan_id', $request->cable)->count() == 1) {
                    $cable = DB::table('cable_id')->where('plan_id', $request->cable)->first();
                    $cable_sel = DB::table('cable_sel')->first();
                    $adm = new IUCsend();
                    $cable_name = strtolower($cable->cable_name);

                    // Fix: Bypass validation for Showmax (always return success with placeholder name)
                    if (strpos($cable_name, 'showmax') !== false) {
                        return response()->json([
                            'status' => 'success',
                            'name' => 'Showmax Subscriber'
                        ]);
                    }

                    $check_now = $cable_sel->$cable_name;
                    $sending_data = [
                        'iuc' => $request->iuc,
                        'cable' => $request->cable
                    ];
                    // Ensure method exists to avoid fatal error
                    if (method_exists($adm, $check_now)) {
                        $response = $adm->$check_now($sending_data);
                    } else {
                        $response = null;
                    }

                    if (!empty($response)) {
                        return response()->json([
                            'status' => 'success',
                            'name' => $response
                        ]);
                    } else {
                        $errorMessage = (strpos($cable_name, 'showmax') !== false)
                            ? 'Invalid Phone Number'
                            : 'Invalid IUC NUMBER';
                        return response()->json([
                            'status' => 'fail',
                            'message' => $errorMessage
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'inavlid cable id'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'cable id required'
                ])->setStatusCode(403);
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'iuc number required'
            ])->setStatusCode(403);
        }
    }
}
