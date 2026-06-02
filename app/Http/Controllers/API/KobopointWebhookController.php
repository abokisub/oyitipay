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
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        Log::info('Kobopoint webhook DEBUG', [
            'ip' => $request->ip(),
            'payload_length' => strlen($payload),
            'headers' => $request->headers->all(),
            'payload_preview' => substr($payload, 0, 500)
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Check if it's form data
            if ($request->isForm() || str_contains($request->header('Content-Type', ''), 'application/x-www-form-urlencoded')) {
                $data = $request->all();
            } else {
                Log::error('Kobopoint webhook: Invalid JSON payload');
                return response()->json(['error' => 'Invalid JSON payload'], 400);
            }
        }

        // Check if this is a Habukhan Data Purchase Webhook
        // Added stricter checks to prevent hijacking legitimate Kobopoint webhooks
        if (isset($data['status']) && isset($data['request-id']) && isset($data['response']) && !isset($data['data']['transaction_id']) && !isset($data['transaction_id'])) {
            Log::info('Routing payload to Habukhan Data Webhook', ['request_id' => $data['request-id']]);
            $webhookController = app(\App\Http\Controllers\API\WebhookController::class);
            return $webhookController->HabukhanWebhook();
        }

        // Kobopoint sends webhooks with a nested 'data' object
        // e.g., data.transaction_id, data.status
        $transactionId = $data['data']['transaction_id'] ?? ($data['orderNo'] ?? ($data['transaction_id'] ?? null));

        if (!$transactionId) {
            Log::error('Kobopoint webhook: Missing transaction_id', ['payload' => $data]);
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        Log::info('Kobopoint Webhook Received', [
            'transaction_id' => $transactionId,
            'status' => $data['data']['status'] ?? ($data['orderStatus'] ?? 'unknown'),
            'ip' => $request->ip()
        ]);

        // Idempotency check
        $exists = DB::table('webhook_events')->where('event_id', $transactionId)->exists();
        
        if ($exists) {
            Log::info('Duplicate Kobopoint webhook event', ['event_id' => $transactionId]);
            return response()->json(['message' => 'Event already processed'], 200);
        }

        // Store event to prevent duplicates
        DB::table('webhook_events')->insert([
            'event_id' => $transactionId,
            'event_type' => $data['event'] ?? 'deposit',
            'payload' => is_string($payload) ? $payload : json_encode($data),
            'processed' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        dispatch(new ProcessKobopointWebhook($data, $transactionId));

        Log::info('Kobopoint webhook received and queued', [
            'transaction_id' => $transactionId,
            'status' => $data['data']['status'] ?? 'unknown'
        ]);

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
