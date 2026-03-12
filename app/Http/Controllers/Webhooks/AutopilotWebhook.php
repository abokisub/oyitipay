<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutopilotWebhook extends Controller
{
    public function Handle(Request $request)
    {
        Log::info('Autopilot Webhook received:', $request->all());

        if (isset($request->status) && isset($request->data['reference'])) {
            $reference = $request->data['reference'];
            $status = $request->status; // success or fail
            $product = $request->data['product'] ?? ''; // data, airtime, cable, etc.

            // Find matching transaction in any of the potential tables
            $tables = ['data', 'airtime', 'cable', 'cash', 'message'];
            foreach ($tables as $table) {
                $transaction = DB::table($table)->where('api_reference', $reference)->first();
                if ($transaction) {
                    if ($status == 'success') {
                        DB::table($table)->where('api_reference', $reference)->update(['plan_status' => 1]);
                        // If it's a message table, also update its status
                        DB::table('message')->where('transid', $transaction->transid)->update(['plan_status' => 1]);
                    } elseif ($status == 'fail') {
                        // Handle refund if not already refunded
                        if ($transaction->plan_status != 2) {
                            DB::table($table)->where('api_reference', $reference)->update(['plan_status' => 2]);
                            DB::table('message')->where('transid', $transaction->transid)->update(['plan_status' => 2]);

                            // Refund user logic (simplified)
                            $this->refundUser($transaction);
                        }
                    }
                    return response()->json(['status' => 'success'], 200);
                }
            }
        }

        return response()->json(['status' => 'ignored'], 200);
    }

    private function refundUser($transaction)
    {
        // Add refund logic based on table-specific amount columns
        $user = DB::table('user')->where('username', $transaction->username)->first();
        if ($user) {
            $amount = $transaction->amount ?? 0;
            // For airtime_discount etc, we might need more complex logic.
            // This is a placeholder for the user's specific refund implementation.
            DB::table('user')->where('username', $transaction->username)->increment('bal', $amount);
            Log::info("Refunded {$amount} to {$transaction->username} for failed Autopilot transaction {$transaction->transid}");
        }
    }
}
