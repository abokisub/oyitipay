<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MomoPurchase extends Controller
{
    public function Cashin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|numeric|digits:11',
            'amount' => 'required|numeric|integer|not_in:0|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 'fail'], 403);
        }

        $transid = $this->purchase_ref('MOMO_');

        // Auth check (Simplified for brevity, similar to other controllers)
        $user_id = $this->verifytoken($request->token);
        if (!$user_id) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'], 403);
        }
        $user = DB::table('user')->where('id', $user_id)->first();

        if ($user->bal < $request->amount) {
            return response()->json(['status' => 'fail', 'message' => 'Insufficient Balance'], 400);
        }

        // Apply Tier Limits via LimitService
        $limitCheck = \App\Services\LimitService::checkLimit($user, $request->amount);
        if (!$limitCheck['allowed']) {
            return response()->json([
                'status' => 'fail',
                'message' => $limitCheck['message']
            ])->setStatusCode(403);
        }

        $reference = $this->generateAutopilotReference();
        $payload = [
            'accountNumber' => $request->account_number,
            'amount' => (string) $request->amount,
            'reference' => $reference
        ];

        $response = $this->autopilot_request('/v1/momo/psb/cashin', $payload);

        if (isset($response['status']) && $response['status'] == true) {
            DB::table('user')->where('id', $user->id)->decrement('bal', $request->amount);

            // Record for Tier Limits 
            \App\Services\LimitService::recordTransaction($user, $request->amount);

            // Insert history
            DB::table('message')->insert([
                'username' => $user->username,
                'amount' => $request->amount,
                'message' => 'MoMo Cashin to ' . $request->account_number,
                'phone_account' => $request->account_number,
                'oldbal' => $user->bal,
                'newbal' => $user->bal - $request->amount,
                'habukhan_date' => Carbon::now(),
                'plan_status' => 1,
                'transid' => $transid,
                'role' => 'momo',
                'api_reference' => $reference
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'MoMo Cashin successful',
                'transid' => $transid
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'message' => $response['message'] ?? 'MoMo Cashin failed'
        ], 400);
    }
}
