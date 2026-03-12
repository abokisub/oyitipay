<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    // Habukhan developer
    public static function send_mail($user_data, $template, $attachment = null)
    {
        try {
            // Auto-inject common metadata if missing
            if (!isset($user_data['ip_address'])) {
                $user_data['ip_address'] = request()->ip();
            }
            if (!isset($user_data['device'])) {
                $ua = request()->header('User-Agent');
                $device = 'Web Browser';
                if (stripos($ua, 'android') !== false)
                    $device = 'Android Device';
                else if (stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false)
                    $device = 'iOS Device';
                else if (stripos($ua, 'dart') !== false)
                    $device = 'Mobile App';
                $user_data['device'] = $device;
            }
            if (!isset($user_data['location'])) {
                $user_data['location'] = 'Lagos, Nigeria'; // Default or lookup if possible
            }
            if (!isset($user_data['app_name'])) {
                $user_data['app_name'] = config('app.name');
            }
            if (!isset($user_data['sender_mail'])) {
                $user_data['sender_mail'] = config('mail.from.address');
            }

            Mail::send($template, $user_data, function ($message) use ($user_data, $attachment) {
                $message->to($user_data['email'], $user_data['username'])
                    ->subject($user_data['title']);
                $message->from(config('mail.from.address'), $user_data['app_name']);

                if ($attachment) {
                    $message->attachData($attachment['data'], $attachment['name'], [
                        'mime' => $attachment['mime']
                    ]);
                }
            });

            if (Mail::failures()) {
                Log::error('Mail sending failed for: ' . $user_data['email']);
                Log::error('Mail Data: ' . json_encode($user_data));
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('Mail sending exception: ' . $e->getMessage());
            Log::error('Mail Data dump: ' . json_encode($user_data));
            return false;
        }
    }
}