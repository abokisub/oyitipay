<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Voucher;

class VoucherController extends Controller
{
    // Admin routes
    public function AdminListVouchers(Request $request)
    {
        $vouchers = Voucher::orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'vouchers' => $vouchers]);
    }

    public function AdminCreateVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:airtime,data',
            'network' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()]);
        }

        $code = strtoupper(Str::random(12));
        
        $voucher = new Voucher();
        $voucher->code = $code;
        $voucher->type = $request->type;
        $voucher->network = $request->network;
        $voucher->status = 'unused';
        
        if ($request->type === 'airtime') {
            $voucher->amount = $request->amount;
            $voucher->vtu_type = $request->vtu_type; // VTU or Share and Sell
        } else {
            $plan = DB::table('data_plan')->where('plan_id', $request->data_plan_id)->first();
            if (!$plan) {
                return response()->json(['status' => 'fail', 'message' => 'Invalid data plan']);
            }
            $voucher->data_plan_id = $request->data_plan_id;
            // set an estimated amount from plan
            $voucher->amount = $plan->smart ?? $plan->api ?? $plan->agent ?? 0;
        }

        $voucher->save();

        return response()->json(['status' => 'success', 'message' => 'Voucher created successfully', 'voucher' => $voucher]);
    }

    public function AdminDeleteVoucher(Request $request, $id)
    {
        $voucher = Voucher::find($id);
        if ($voucher) {
            $voucher->delete();
            return response()->json(['status' => 'success', 'message' => 'Voucher deleted successfully']);
        }
        return response()->json(['status' => 'fail', 'message' => 'Voucher not found']);
    }

    public function GetDataPlans(Request $request)
    {
        $network = $request->network;
        $plans = DB::table('data_plan')->where('network', $network)->where('plan_status', 1)->get();
        return response()->json(['status' => 'success', 'plans' => $plans]);
    }

    public function PreviewVoucher(Request $request)
    {
        // Require authentication
        $authHeader = $request->header('Authorization');
        if (strpos($authHeader, 'Token ') === 0) {
            $authHeader = substr($authHeader, 6);
        } elseif (strpos($authHeader, 'Bearer ') === 0) {
            $authHeader = substr($authHeader, 7);
        }
        $accessToken = trim($authHeader);

        $user = DB::table('user')->where(function ($query) use ($accessToken) {
            $query->where('apikey', $accessToken)
                ->orWhere('app_key', $accessToken)
                ->orWhere('habukhan_key', $accessToken);
        })->where('status', 1)->first();

        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()]);
        }

        $code = $request->code;
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid voucher code']);
        }

        if ($voucher->status !== 'unused') {
            return response()->json(['status' => 'fail', 'message' => 'Voucher has already been used']);
        }

        // Return preview data
        return response()->json([
            'status' => 'success',
            'voucher' => [
                'type' => $voucher->type,
                'network' => $voucher->network,
                'amount' => $voucher->amount,
                'vtu_type' => $voucher->vtu_type,
            ]
        ]);
    }

    // User claim route
    public function ClaimVoucher(Request $request)
    {
        // Require authentication
        $authHeader = $request->header('Authorization');
        if (strpos($authHeader, 'Token ') === 0) {
            $authHeader = substr($authHeader, 6);
        } elseif (strpos($authHeader, 'Bearer ') === 0) {
            $authHeader = substr($authHeader, 7);
        }
        $accessToken = trim($authHeader);

        $user = DB::table('user')->where(function ($query) use ($accessToken) {
            $query->where('apikey', $accessToken)
                ->orWhere('app_key', $accessToken)
                ->orWhere('habukhan_key', $accessToken);
        })->where('status', 1)->first();

        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'phone' => 'required|numeric|digits:11'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()]);
        }

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid voucher code']);
        }

        if ($voucher->status === 'used') {
            return response()->json(['status' => 'fail', 'message' => 'This voucher has already been used']);
        }

        // Fulfill voucher logic - we fund wallet temporally or we directly pass it to DataPurchase
        // For simplicity, we directly call DataPurchase or AirtimePurchase
        // but simulate wallet balance

        DB::beginTransaction();

        $voucher->status = 'used';
        $voucher->used_by = $user->username;
        $voucher->used_at = now();
        $voucher->save();

        // Let's fund the user's wallet with the voucher amount so they can 'pay' for the transaction normally
        // Then we deduct it back via the standard flow. Wait, if we use the standard flow, it deducts from balance.
        // If we fund them $voucher->amount, and the standard flow deducts $voucher->amount, they net 0.
        
        $current_bal = $user->bal;
        DB::table('user')->where('id', $user->id)->update(['bal' => $current_bal + $voucher->amount]);

        DB::commit();

        // Dispatch purchase logic via internal request or HTTP request
        $system = "APP";
        $transid = 'VOUCHER_' . strtoupper(Str::random(10));
        
        $originalAuthHeader = $request->header('Authorization');
        
        $networkRecord = DB::table('network')->where('network', $voucher->network)->first();
        $networkPlanId = $networkRecord ? $networkRecord->plan_id : $voucher->network;
        
        if ($voucher->type === 'data') {
            // Internal call
            $req = Request::create('/api/data', 'POST', [
                'network' => $networkPlanId,
                'phone' => $request->phone,
                'bypass' => true,
                'data_plan' => $voucher->data_plan_id,
                'request-id' => $transid,
                'pin' => $user->pin,
                'user_id' => $user->id,
                'token' => $accessToken
            ]);
            $req->headers->set('Authorization', $originalAuthHeader);
            $res = app()->handle($req);
            
            $data = json_decode($res->getContent(), true);
            if (!isset($data['status']) || $data['status'] !== 'success') {
                // reverse voucher
                $voucher->status = 'unused';
                $voucher->used_by = null;
                $voucher->used_at = null;
                $voucher->save();
                
                // reverse fund
                DB::table('user')->where('id', $user->id)->decrement('bal', $voucher->amount);
                
                return response()->json(['status' => 'fail', 'message' => 'Failed to process data: ' . ($data['message'] ?? 'Unknown Error')]);
            }
        } else {
             $req = Request::create('/api/topup', 'POST', [
                'network' => $networkPlanId,
                'phone' => $request->phone,
                'amount' => (int) $voucher->amount,
                'plan_type' => $voucher->vtu_type,
                'bypass' => true,
                'request-id' => $transid,
                'pin' => $user->pin,
                'user_id' => $user->id,
                'token' => $accessToken
            ]);
            $req->headers->set('Authorization', $originalAuthHeader);
            $res = app()->handle($req);
            
            $data = json_decode($res->getContent(), true);
            if (!isset($data['status']) || $data['status'] !== 'success') {
                // reverse voucher
                $voucher->status = 'unused';
                $voucher->used_by = null;
                $voucher->used_at = null;
                $voucher->save();
                
                // reverse fund
                DB::table('user')->where('id', $user->id)->decrement('bal', $voucher->amount);
                
                return response()->json(['status' => 'fail', 'message' => 'Failed to process airtime: ' . ($data['message'] ?? 'Unknown Error')]);
            }
        }

        // Record Voucher Credit Transaction
        DB::table('message')->insert([
            'username' => $user->username,
            'message' => 'Voucher Claim - ' . $voucher->network . ' ' . strtoupper($voucher->type),
            'transid' => 'VC_' . strtoupper(\Illuminate\Support\Str::random(10)),
            'amount' => $voucher->amount,
            'oldbal' => $current_bal,
            'newbal' => $current_bal + $voucher->amount,
            'trans_status' => 1,
            'trans_date' => date('Y-m-d H:i:s'),
            'trans_month' => date('F'),
            'trans_year' => date('Y'),
            'status' => 'credit',
            'service' => 'VOUCHER',
            'transaction_channel' => 'APP'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Voucher claimed and processed successfully!']);
    }
}

