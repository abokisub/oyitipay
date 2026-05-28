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

        $receivedSignature = $request->header('X-Webhook-Signature') 
                          ?? $request->header('X-PointWave-Signature')
                          ?? $request->header('X-Kobopoint-Signature')
                          ?? $request->header('X-Signature');

        $webhookSecret = config('kobopoint.secret_key', env('KOBOPOINT_SECRET_KEY'));
        
        Log::info('Kobopoint webhook DEBUG', [
            'signature_header' => $receivedSignature,
            'payload_length' => strlen($payload),
        ]);

        if (!$receivedSignature) {
            Log::warning('Kobopoint webhook missing signature');
            return response()->json(['error' => 'Missing signature'], 401);
        }

        if (strpos($receivedSignature, 'sha256=') === 0) {
            $receivedSignature = substr($receivedSignature, 7);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid Kobopoint webhook signature', [
                'received' => $receivedSignature,
                'expected' => $expectedSignature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        Log::info('Kobopoint webhook signature verified successfully');

        $data = json_decode($payload, true);
        
        if (!$data) {
            Log::error('Invalid JSON payload from Kobopoint');
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        $transactionId = $data['transaction_id'] ?? null;
        $notificationStatus = $data['notification_status'] ?? 'successful';

        if (!$transactionId) {
            Log::error('Missing transaction_id in Kobopoint webhook');
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        $exists = DB::table('webhook_events')->where('event_id', $transactionId)->exists();
        
        if ($exists) {
            Log::info('Duplicate Kobopoint webhook event', ['event_id' => $transactionId]);
            return response()->json(['message' => 'Event already processed'], 200);
        }

        DB::table('webhook_events')->insert([
            'event_id' => $transactionId,
            'event_type' => $notificationStatus,
            'payload' => $payload,
            'processed' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        dispatch(new ProcessKobopointWebhook($data, $transactionId));

        Log::info('Kobopoint webhook received and queued', [
            'transaction_id' => $transactionId,
        ]);

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
