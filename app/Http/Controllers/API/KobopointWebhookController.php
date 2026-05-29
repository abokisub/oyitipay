<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKobopointWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KobopointWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Kobopoint
     * POST /api/kobopoint-webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();

        Log::info('Kobopoint webhook DEBUG', [
            'ip'             => $request->ip(),
            'payload_length' => strlen($payload),
            'headers'        => $request->headers->all(),
        ]);

        $data = json_decode($payload, true);

        if (!$data) {
            Log::error('Kobopoint webhook: Invalid JSON payload');
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Kobopoint sends webhooks with a nested 'data' object
        // e.g., data.transaction_id, data.status
        $transactionId = $data['data']['transaction_id'] ?? ($data['orderNo'] ?? ($data['transaction_id'] ?? null));

        if (!$transactionId) {
            Log::error('Kobopoint webhook: Missing transaction_id', ['payload' => $data]);
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        Log::info('Kobopoint Webhook Received', [
            'ip'             => $request->ip(),
            'transaction_id' => $transactionId,
            'payload'        => $data,
        ]);

        // Idempotency check
        $exists = DB::table('webhook_events')->where('event_id', $transactionId)->exists();

        if ($exists) {
            Log::info('Duplicate Kobopoint webhook event', ['event_id' => $transactionId]);
            return response()->json(['message' => 'Event already processed'], 200);
        }

        $notificationStatus = $data['data']['status'] ?? ($data['status'] ?? 'successful');

        DB::table('webhook_events')->insert([
            'event_id'   => $transactionId,
            'event_type' => $notificationStatus,
            'payload'    => $payload,
            'processed'  => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        dispatch(new ProcessKobopointWebhook($data, $transactionId));

        Log::info('Kobopoint webhook received and queued', [
            'transaction_id' => $transactionId,
        ]);

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
