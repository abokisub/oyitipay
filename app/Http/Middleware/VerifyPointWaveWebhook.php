<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyPointWaveWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Extract signature from header (PointWave uses X-PointWave-Signature)
        $signature = $request->header('X-PointWave-Signature');
        
        if (!$signature) {
            Log::channel('security')->warning('PointWave webhook missing signature', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            // For now, allow without signature (will be enforced once webhook secret is configured)
            return $next($request);
        }

        // Get webhook secret from environment
        $webhookSecret = env('POINTWAVE_WEBHOOK_SECRET');
        
        if (!$webhookSecret) {
            Log::channel('security')->info('PointWave webhook secret not configured, skipping verification', [
                'ip' => $request->ip(),
            ]);
            
            // Allow the request to proceed if secret is not configured
            return $next($request);
        }

        // Get raw request payload
        $payload = $request->getContent();
        
        // Calculate expected signature (PointWave sends raw HMAC-SHA256, no prefix)
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Use constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('security')->warning('Invalid PointWave webhook signature', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'received_signature' => substr($signature, 0, 20) . '...',
                'payload_preview' => substr($payload, 0, 100),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature'
            ], 401);
        }

        // Signature is valid, proceed with request
        Log::channel('pointwave')->info('PointWave webhook signature verified', [
            'ip' => $request->ip(),
            'event' => $request->input('event'),
        ]);

        return $next($request);
    }
}
