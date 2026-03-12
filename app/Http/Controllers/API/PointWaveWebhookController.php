<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPointWaveWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointWaveWebhookController extends Controller
{
    /**
     * Handle incoming webhook from PointWave
     * POST /webhooks/pointwave
     */
    public function handleWebhook(Request $request)
    {
        // Step 1: Get raw payload (IMPORTANT: Use getContent(), not json())
        $payload = $request->getContent();

        // Step 2: Get signature from header (try multiple header names)
        $receivedSignature = $request->header('X-Webhook-Signature') 
                          ?? $request->header('X-PointWave-Signature')
                          ?? $request->header('X-Signature');

        // Step 3: Get SECRET KEY from config (PointWave now uses API secret key, not separate webhook secret)
        // This is the same key used for API authentication
        $webhookSecret = config('services.pointwave.secret_key');
        
        // DEBUG: Log everything for signature debugging
        Log::info('PointWave webhook DEBUG', [
            'all_headers' => $request->headers->all(),
            'signature_header' => $receivedSignature,
            'payload_length' => strlen($payload),
            'payload_first_100' => substr($payload, 0, 100),
            'secret_length' => strlen($webhookSecret),
            'secret_first_10' => substr($webhookSecret, 0, 10),
        ]);

        // Step 4: Check if signature exists
        if (!$receivedSignature) {
            Log::warning('PointWave webhook missing signature', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'headers' => $request->headers->all()
            ]);
            
            return response()->json(['error' => 'Missing signature'], 401);
        }

        // Step 5: Remove "sha256=" prefix if present (some webhooks send it with prefix)
        if (strpos($receivedSignature, 'sha256=') === 0) {
            $receivedSignature = substr($receivedSignature, 7); // Remove "sha256=" (7 characters)
        }

        // Step 6: Compute expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Step 7: Compare signatures (timing-safe comparison)
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            Log::warning('Invalid PointWave webhook signature', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'received' => $receivedSignature,
                'expected' => $expectedSignature,
                'payload' => $payload,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        Log::info('PointWave webhook signature verified successfully');

        // Step 7: Signature is valid! Parse the payload
        $data = json_decode($payload, true);
        
        if (!$data) {
            Log::error('Invalid JSON payload from PointWave');
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Step 8: Get event details
        $eventType = $data['event'] ?? null;
        $eventId = $request->header('X-PointWave-Event-ID') ?? $data['event_id'] ?? null;

        if (!$eventId) {
            Log::error('Missing event_id in PointWave webhook');
            return response()->json(['error' => 'Missing event_id'], 400);
        }

        // Step 9: Check for duplicate events (idempotency)
        $exists = DB::table('webhook_events')->where('event_id', $eventId)->exists();
        
        if ($exists) {
            Log::info('Duplicate PointWave webhook event', ['event_id' => $eventId]);
            return response()->json(['message' => 'Event already processed'], 200);
        }

        // Step 10: Store event to prevent duplicates
        DB::table('webhook_events')->insert([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'processed' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Step 11: Return 200 immediately (VERY IMPORTANT!)
        // Queue the actual processing - don't process here!
        dispatch(new ProcessPointWaveWebhook($data, $eventId));

        Log::info('PointWave webhook received and queued', [
            'event_id' => $eventId,
            'event_type' => $eventType
        ]);

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
