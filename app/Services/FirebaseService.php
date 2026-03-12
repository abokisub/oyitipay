<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = storage_path('app/firebase/firebase-auth.json');

        if (!file_exists($credentialsPath)) {
            Log::error("Firebase credentials file not found at: {$credentialsPath}");
            return;
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send a notification to a specific FCM token.
     *
     * @param string $token The recipient's FCM token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string|null $image Optional image URL
     * @param bool $is_data_only Whether to send only data payload (for professional background handling)
     * @return bool
     */
    public function sendNotification($token, $title, $body, $data = [], $image = null, $is_data_only = false)
    {
        if (!$this->messaging) {
            Log::warning("Firebase Messaging not initialized. Check credentials.");
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $token);

            if (!$is_data_only) {
                $notification = Notification::fromArray([
                    'title' => $title,
                    'body' => $body,
                    'image' => $image
                ]);
                $message = $message->withNotification($notification);
            }

            // Standardize all data values to strings (FCM requirement)
            $payload = array_merge($data, [
                'title' => (string)$title,
                'body' => (string)$body,
                'image' => (string)($image ?? ''),
                'imageUrl' => (string)($image ?? ''),
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);

            $stringData = [];
            foreach ($payload as $key => $value) {
                $stringData[(string)$key] = (string)$value;
            }
            $message = $message->withData($stringData);

            // Professional Android Config
            $androidConfig = ['priority' => 'high'];

            // Only add notification block if NOT data-only
            // If data-only, AndroidConfig['notification'] triggers an OS skeleton notification
            if (!$is_data_only) {
                $androidConfig['notification'] = [
                    'channel_id' => $data['channel_id'] ?? 'high_importance_channel',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ];
                if ($image) {
                    $androidConfig['notification']['image'] = $image;
                }
                
                // Debug log the channel being used
                Log::info("FCM Android Config", [
                    'channel_id' => $androidConfig['notification']['channel_id'],
                    'has_image' => !empty($image),
                    'data_keys' => array_keys($stringData)
                ]);
            }

            $message = $message->withAndroidConfig(AndroidConfig::fromArray($androidConfig));

            $this->messaging->send($message);
            Log::info("FCM Notification sent successfully to token: " . substr($token, 0, 10) . "...");
            return true;
        }
        catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Token not found - remove from database
            Log::warning("FCM Token not found, cleaning up: " . substr($token, 0, 20) . "...");
            $this->cleanupInvalidToken($token, "Requested entity was not found");
            return false;
        }
        catch (\Kreait\Firebase\Exception\MessagingException $e) {
            // Other messaging errors (SenderId mismatch, etc.)
            $errorMessage = $e->getMessage();
            Log::error("FCM Messaging Error: " . $errorMessage);
            
            // Cleanup tokens with SenderId mismatch
            if (stripos($errorMessage, 'SenderId mismatch') !== false) {
                Log::warning("SenderId mismatch detected, cleaning up token: " . substr($token, 0, 20) . "...");
                $this->cleanupInvalidToken($token, $errorMessage);
            }
            
            return false;
        }
        catch (\Exception $e) {
            Log::error("Failed to send FCM Notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a notification to multiple FCM tokens in batches.
     *
     * @param array $tokens List of recipient FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param string|null $image Optional image URL
     * @param bool $is_data_only Whether to send only data payload
     * @return array Summary of success and failures
     */
    public function sendMulticastNotification(array $tokens, $title, $body, $data = [], $image = null, $is_data_only = false)
    {
        if (!$this->messaging) {
            Log::warning("Firebase Messaging not initialized. Check credentials.");
            return ['success' => 0, 'failure' => count($tokens)];
        }

        // Filter out empty tokens
        $tokens = array_filter(array_unique($tokens));
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        try {
            $message = CloudMessage::new ();

            if (!$is_data_only) {
                $notification = Notification::fromArray([
                    'title' => $title,
                    'body' => $body,
                    'image' => $image
                ]);
                $message = $message->withNotification($notification);
            }

            // Standardize all data values to strings
            $payload = array_merge($data, [
                'title' => (string)$title,
                'body' => (string)$body,
                'image' => (string)($image ?? ''),
                'imageUrl' => (string)($image ?? ''), // Support both for compatibility
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);

            $stringData = [];
            foreach ($payload as $key => $value) {
                $stringData[(string)$key] = (string)$value;
            }
            $message = $message->withData($stringData);

            // Professional Android Config
            $androidConfig = ['priority' => 'high'];

            if (!$is_data_only) {
                $androidConfig['notification'] = [
                    'channel_id' => $data['channel_id'] ?? 'admin_broadcast_channel',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ];
                if ($image) {
                    $androidConfig['notification']['image'] = $image;
                }
            }

            $message = $message->withAndroidConfig(AndroidConfig::fromArray($androidConfig));

            $chunks = array_chunk($tokens, 500); // FCM multicast limit is 500
            $totalSuccess = 0;
            $totalFailure = 0;

            foreach ($chunks as $chunkIndex => $chunk) {
                $report = $this->messaging->sendMulticast($message, $chunk);
                $totalSuccess += $report->successes()->count();
                $totalFailure += $report->failures()->count();
                
                // Log failures and cleanup invalid tokens
                if ($report->hasFailures()) {
                    foreach ($report->failures()->getItems() as $failure) {
                        $failedToken = $failure->target()->value();
                        $error = $failure->error()->getMessage();
                        
                        Log::warning("FCM Token Failed", [
                            'token' => substr($failedToken, 0, 20) . '...',
                            'error' => $error
                        ]);
                        
                        // Auto-cleanup invalid tokens from database
                        $this->cleanupInvalidToken($failedToken, $error);
                    }
                }
            }

            Log::info("Multicast FCM sent: {$totalSuccess} success, {$totalFailure} failures.");
            return ['success' => $totalSuccess, 'failure' => $totalFailure];
        }
        catch (\Exception $e) {
            Log::error("Failed to send Multicast FCM: " . $e->getMessage());
            return ['success' => 0, 'failure' => count($tokens)];
        }
    }

    /**
     * Cleanup invalid FCM token from database
     *
     * @param string $token The invalid token
     * @param string $error The error message
     * @return void
     */
    private function cleanupInvalidToken($token, $error)
    {
        try {
            // Determine if token should be removed based on error type
            $shouldRemove = false;
            
            // Remove tokens that are definitely invalid
            if (stripos($error, 'Requested entity was not found') !== false ||
                stripos($error, 'registration token is not a valid') !== false ||
                stripos($error, 'SenderId mismatch') !== false ||
                stripos($error, 'invalid registration') !== false) {
                $shouldRemove = true;
            }
            
            if ($shouldRemove) {
                $deleted = DB::table('user')
                    ->where('app_token', $token)
                    ->update(['app_token' => null]);
                
                if ($deleted > 0) {
                    Log::info("Cleaned up invalid FCM token from {$deleted} user(s)", [
                        'token' => substr($token, 0, 20) . '...',
                        'reason' => $error
                    ]);
                }
            }
        }
        catch (\Exception $e) {
            Log::error("Failed to cleanup invalid FCM token: " . $e->getMessage());
        }
    }
}