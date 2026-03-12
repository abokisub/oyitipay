<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function Gmail(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $general = $this->general();
                    $habukhan_search = ['{username}', '{email}', '{fullname}', '{phone}', '{webhook}', '{apikey}', '{address}', '{ref}', '{type}', '{wema}', '{rolex}', '{ster}', '{fed}', '{otp}', '{user_limit}', '{bal}', '{rebal}'];
                    if ($request->status == 'ALL') {
                        $all_user = DB::table('user')->get();
                    }
                    else if ($request->status == 'CUSTOM') {
                        $all_user = DB::table('user')->where('username', $request->user_username)->get();
                    }
                    else {
                        $all_user = DB::table('user')->where('type', $request->status)->get();
                    }
                    foreach ($all_user as $user) {
                        $moniepoint_acc = DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first()->account_number ?? 'Generating...';
                        $change_habukhan = [$user->username, $user->email, $user->name, $user->phone, $user->webhook, $user->apikey, $user->address, $user->ref, $user->type, $user->paystack_account, $user->palmpay ?? 'N/A', $moniepoint_acc, $user->kolomoni_mfb ?? 'N/A', $user->otp, $user->user_limit, '₦' . number_format($user->bal, 2), '₦' . number_format($user->refbal, 2)];
                        $real_message = str_replace($habukhan_search, $change_habukhan, $request->message);
                        $email_data = [
                            'name' => $user->name,
                            'email' => $user->email,
                            'username' => $user->username,
                            'title' => $request->title,
                            'sender_mail' => $general->app_email,
                            'messages' => $real_message,
                            'app_name' => config('app.name'),
                        ];
                        MailController::send_mail($email_data, 'email.notif');
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function System(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // Handle image upload if provided
                    $imagePath = null;
                    if ($request->hasFile('image')) {
                        $image = $request->file('image');
                        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                        // Create notifications directory if it doesn't exist
                        if (!file_exists(public_path('notifications'))) {
                            mkdir(public_path('notifications'), 0755, true);
                        }

                        $image->move(public_path('notifications'), $imageName);
                        $imagePath = '/notifications/' . $imageName;
                    }
                    elseif ($request->image_url) {
                        // Fallback for old URL-based system
                        $imagePath = $request->image_url;
                    }

                    // Create broadcast ID for grouping
                    $broadcastId = uniqid('notif_', true);

                    $habukhan_search = ['{username}', '{email}', '{fullname}', '{phone}', '{webhook}', '{apikey}', '{address}', '{ref}', '{type}', '{wema}', '{rolex}', '{ster}', '{fed}', '{otp}', '{user_limit}', '{bal}', '{rebal}'];
                    if ($request->status == 'ALL') {
                        $all_user = DB::table('user')->get();
                    }
                    else if ($request->status == 'CUSTOM') {
                        $all_user = DB::table('user')->where('username', $request->user_username)->get();
                    }
                    else {
                        $all_user = DB::table('user')->where('type', $request->status)->get();
                    }

                    foreach ($all_user as $user) {
                        $moniepoint_acc = DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first()->account_number ?? 'Generating...';
                        $change_habukhan = [$user->username, $user->email, $user->name, $user->phone, $user->webhook, $user->apikey, $user->address, $user->ref, $user->type, $user->paystack_account, $user->palmpay ?? 'N/A', $moniepoint_acc, $user->kolomoni_mfb ?? 'N/A', $user->otp, $user->user_limit, '₦' . number_format($user->bal, 2), '₦' . number_format($user->refbal, 2)];
                        $real_message = str_replace($habukhan_search, $change_habukhan, $request->message);

                        DB::table('notif')->insert([
                            'username' => $user->username,
                            'message' => $real_message,
                            'date' => $this->system_date(),
                            'habukhan' => 0,
                            'image_url' => $imagePath,
                            'broadcast_id' => $broadcastId
                        ]);
                    }

                    // 1. Collect all tokens for the target users
                    $tokens = $all_user->pluck('app_token')->filter()->toArray();

                    // 2. Send Multicast Firebase Notification
                    if (!empty($tokens)) {
                        try {
                            $firebase = new FirebaseService();
                            $firebase->sendMulticastNotification(
                                $tokens,
                                $request->title ?? config('app.name'),
                                $request->message, // Message from request
                            [
                                'type' => 'broadcast',
                                'broadcast_id' => $broadcastId,
                                'channel_id' => 'admin_broadcast_channel'
                            ],
                                !empty($imagePath) ? url($imagePath) : null,
                                false // Show notification even when app is closed
                            );
                        }
                        catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning('Multicast Firebase failed: ' . $e->getMessage());
                        }
                    }

                    // Save broadcast history
                    DB::table('notification_broadcasts')->insert([
                        'broadcast_id' => $broadcastId,
                        'message' => $request->message,
                        'image_path' => $imagePath,
                        'target_type' => $request->status,
                        'target_username' => $request->user_username ?? null,
                        'sent_count' => $all_user->count(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Notification sent successfully',
                        'data' => [
                            'broadcast_id' => $broadcastId,
                            'sent_count' => $all_user->count(),
                            'image_path' => $imagePath
                        ]
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function Bulksms(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            set_time_limit(0);
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $habukhan_search = ['{username}', '{email}', '{fullname}', '{phone}', '{webhook}', '{apikey}', '{address}', '{ref}', '{type}', '{wema}', '{rolex}', '{ster}', '{fed}', '{otp}', '{user_limit}', '{bal}', '{rebal}'];
                    if ($request->status == 'ALL') {
                        $all_user = DB::table('user')->get();
                    }
                    else if ($request->status == 'CUSTOM') {
                        $all_user = DB::table('user')->where('username', $request->user_username)->get();
                    }
                    else {
                    }
                    foreach ($all_user as $user) {
                        $moniepoint_acc = DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first()->account_number ?? 'Generating...';
                        $change_habukhan = [$user->username, $user->email, $user->name, $user->phone, $user->webhook, $user->apikey, $user->address, $user->ref, $user->type, $user->paystack_account, $user->palmpay ?? 'N/A', $moniepoint_acc, $user->kolomoni_mfb ?? 'N/A', $user->otp, $user->user_limit, '₦' . number_format($user->bal, 2), '₦' . number_format($user->refbal, 2)];
                        $real_message = str_replace($habukhan_search, $change_habukhan, $request->message);
                        $habukhan_api = DB::table('other_api')->first();
                        $r = array(
                            "user" => $habukhan_api->hollatag_username,
                            "pass" => $habukhan_api->hollatag_password,
                            "from" => config('app.name'),
                            "to" => $user->phone,
                            "msg" => $real_message,
                            "type" => 0,
                        );

                        $url = 'https://sms.hollatags.com/api/send/';
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($r));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_exec($ch);
                        curl_close($ch);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
}