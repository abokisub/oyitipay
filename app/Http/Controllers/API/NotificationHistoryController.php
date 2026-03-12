<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class NotificationHistoryController extends Controller
{
    /**
     * Get all notification broadcasts
     * GET /api/admin/notifications/history/{id}/secure
     */
    public function getNotifications(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $broadcasts = DB::table('notification_broadcasts')
                        ->orderBy('created_at', 'desc')
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'data' => $broadcasts
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    /**
     * Resend existing notification
     * POST /api/admin/notifications/resend/{notificationId}/{id}/secure
     */
    public function resendNotification(Request $request, $notificationId)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $broadcast = DB::table('notification_broadcasts')
                        ->where('broadcast_id', $notificationId)
                        ->first();

                    if (!$broadcast) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Notification not found'
                        ], 404);
                    }

                    // Get target users
                    if ($broadcast->target_type == 'ALL') {
                        $all_user = DB::table('user')->get();
                    }
                    else if ($broadcast->target_type == 'CUSTOM') {
                        $all_user = DB::table('user')->where('username', $broadcast->target_username)->get();
                    }
                    else {
                        $all_user = DB::table('user')->where('type', $broadcast->target_type)->get();
                    }

                    // Create new broadcast ID
                    $newBroadcastId = uniqid('notif_', true);
                    $habukhan_search = ['{username}', '{email}', '{fullname}', '{phone}', '{webhook}', '{apikey}', '{address}', '{ref}', '{type}', '{wema}', '{rolex}', '{ster}', '{fed}', '{otp}', '{user_limit}', '{bal}', '{rebal}'];

                    // 1. Collect tokens and prepare push data
                    $tokens = $all_user->pluck('app_token')->filter()->toArray();
                    $absoluteImageUrl = $broadcast->image_path ? url($broadcast->image_path) : null;

                    foreach ($all_user as $user) {
                        $moniepoint_acc = DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first()->account_number ?? 'Generating...';
                        $change_habukhan = [$user->username, $user->email, $user->name, $user->phone, $user->webhook, $user->apikey, $user->address, $user->ref, $user->type, $user->paystack_account, $user->rolex, $moniepoint_acc, $user->fed, $user->otp, $user->user_limit, '₦' . number_format($user->bal, 2), '₦' . number_format($user->refbal, 2)];
                        $real_message = str_replace($habukhan_search, $change_habukhan, $broadcast->message);

                        DB::table('notif')->insert([
                            'username' => $user->username,
                            'message' => $real_message,
                            'date' => $this->system_date(),
                            'habukhan' => 0,
                            'image_url' => $broadcast->image_path,
                            'broadcast_id' => $newBroadcastId
                        ]);
                    }

                    // 2. Send Multicast Firebase Notification
                    if (!empty($tokens)) {
                        try {
                            $firebase = new \App\Services\FirebaseService();
                            $firebase->sendMulticastNotification(
                                $tokens,
                                config('app.name'), // Use app name as title for broadcasts
                                $broadcast->message,
                            [
                                'type' => 'broadcast',
                                'broadcast_id' => $newBroadcastId,
                                'channel_id' => 'admin_broadcast_channel'
                            ],
                                $absoluteImageUrl,
                                false // Show notification even when app is closed
                            );
                        }
                        catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning('Resend Multicast Firebase failed: ' . $e->getMessage());
                        }
                    }

                    // Save new broadcast history
                    DB::table('notification_broadcasts')->insert([
                        'broadcast_id' => $newBroadcastId,
                        'message' => $broadcast->message,
                        'image_path' => $broadcast->image_path,
                        'target_type' => $broadcast->target_type,
                        'target_username' => $broadcast->target_username,
                        'sent_count' => $all_user->count(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Notification resent successfully',
                        'data' => [
                            'broadcast_id' => $newBroadcastId,
                            'sent_count' => $all_user->count()
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    /**
     * Update notification
     * POST /api/admin/notifications/update/{notificationId}/{id}/secure
     */
    public function updateNotification(Request $request, $notificationId)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $broadcast = DB::table('notification_broadcasts')
                        ->where('broadcast_id', $notificationId)
                        ->first();

                    if (!$broadcast) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Notification not found'
                        ], 404);
                    }

                    $updateData = [];

                    if ($request->has('message')) {
                        $updateData['message'] = $request->message;
                    }

                    // Handle new image upload
                    if ($request->hasFile('image')) {
                        // Delete old image if exists
                        if ($broadcast->image_path && file_exists(public_path($broadcast->image_path))) {
                            unlink(public_path($broadcast->image_path));
                        }

                        $image = $request->file('image');
                        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('notifications'), $imageName);
                        $updateData['image_path'] = '/notifications/' . $imageName;
                    }

                    $updateData['updated_at'] = now();

                    DB::table('notification_broadcasts')
                        ->where('broadcast_id', $notificationId)
                        ->update($updateData);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Notification updated successfully'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    /**
     * Delete notification
     * DELETE /api/admin/notifications/delete/{notificationId}/{id}/secure
     */
    public function deleteNotification(Request $request, $notificationId)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $broadcast = DB::table('notification_broadcasts')
                        ->where('broadcast_id', $notificationId)
                        ->first();

                    if (!$broadcast) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Notification not found'
                        ], 404);
                    }

                    // Delete image file if exists
                    if ($broadcast->image_path && file_exists(public_path($broadcast->image_path))) {
                        unlink(public_path($broadcast->image_path));
                    }

                    // Delete broadcast record
                    DB::table('notification_broadcasts')
                        ->where('broadcast_id', $notificationId)
                        ->delete();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Notification deleted successfully'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
}