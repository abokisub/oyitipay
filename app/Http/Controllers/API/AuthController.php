<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        set_time_limit(300); // Increased time limit
        ignore_user_abort(true); // Continue processing even if user disconnects
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'name' => 'required|max:199|min:3',
                'email' => 'required|unique:user,email|max:255|email',
                'phone' => 'required|numeric|unique:user,phone|digits:11',
                'password' => 'required|min:8',
                'username' => 'required|unique:user,username|max:12|string|alpha_num',
                'pin' => 'nullable|numeric|digits:4'
            ], [
                'name.required' => 'Full Name is Required',
                'email.required' => 'E-mail is Required',
                'phone.required' => 'Phone Number Required',
                'password.required' => 'Password Required',
                'username.required' => 'Username Required',
                'username.unique' => 'Username already Taken',
                'phone.unique' => 'Phone Number already Taken',
                'username.max' => 'Username Maximum Length is 12 ' . $request->username,

                'email.unique' => 'Email Already Taken',
                'password.min' => 'Password Not Strong Enough',
                'name.min' => 'Invalid Full Name (Min 3 characters)',
                'name.max' => 'Invalid Full Name',
                'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,

                'pin.numeric' => 'Transaction Pin Numeric',
                'pin.digits' => 'Transaction Pin Digits Must Be 4'
            ]);
            // checking referal user details
            if ($request->ref != null) {
                $check_ref = DB::table('user')
                    ->where('username', '=', $request->ref)
                    ->count();
            }
            if ($validator->fails()) {

                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            } else if (substr($request->phone, 0, 1) != '0') {
                return response()->json([
                    'message' => 'Invalid Phone Number',
                    'status' => 403
                ])->setStatusCode(403);
            } else
                if ($request->ref != null && $check_ref == 0) {
                    return response()->json([
                        'message' => 'Invalid Referral Username You can Leave the Referral Username Box Empty',
                        'status' => '403'
                    ])->setStatusCode(403);
                } else {
                    $user = new User();
                    $user->name = $request->name;
                    $user->username = $request->username;
                    $user->email = $request->email;
                    $user->phone = $request->phone;
                    $user->password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                    // $user->password = Hash::make($request->password);
                    $user->apikey = bin2hex(openssl_random_pseudo_bytes(30));
                    $user->app_key = $user->apikey;
                    $user->bal = '0.00';
                    $user->refbal = '0.00';
                    $user->ref = $request->ref;
                    $user->type = 'SMART';
                    $user->date = Carbon::now("Africa/Lagos");
                    $user->kyc = '0';
                    $user->status = '1'; // Auto-verify all users (OTP disabled)
                    $user->user_limit = $this->habukhan_key()->default_limit;
                    $user->pin = $request->pin ?? null;
                    $user->save();
                    if ($user != null) {
                        $user = DB::table('user')->where(['id' => $user->id])->first();

                        // Fetch settings to check enabled providers
                        try {
                            $settings = DB::table('settings')->select(
                                'palmpay_enabled',
                                'monnify_enabled',
                                'wema_enabled',
                                'xixapay_enabled',
                                'pointwave_enabled',
                                'default_virtual_account'
                            )->first();

                            // Determine which accounts to show based on settings
                            $monnify_enabled = $settings->monnify_enabled ?? true;
                            $wema_enabled = $settings->wema_enabled ?? true;
                            $xixapay_enabled = $settings->xixapay_enabled ?? true;
                            $palmpay_enabled = $settings->palmpay_enabled ?? true;
                            $pointwave_enabled = $settings->pointwave_enabled ?? false;
                            $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                            $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                        } catch (\Exception $e) {
                            $monnify_enabled = false;
                            $wema_enabled = false;
                            $xixapay_enabled = false;
                            $palmpay_enabled = true;
                            $pointwave_enabled = false;
                            $default_virtual_account = 'palmpay';
                        }

                        // Smart Fallback
                        $active_default = $default_virtual_account;
                        if ($active_default == 'wema' && !$wema_enabled)
                            $active_default = null;
                        if ($active_default == 'monnify' && !$monnify_enabled)
                            $active_default = null;
                        if ($active_default == 'xixapay' && !$xixapay_enabled)
                            $active_default = null;
                        if ($active_default == 'palmpay' && !$palmpay_enabled)
                            $active_default = null;



                        try {
                            if ($xixapay_enabled)
                                $this->xixapay_account($user->username);
                        } catch (\Exception $e) {
                            \Log::error("Register Xixapay: " . $e->getMessage());
                        }

                        try {
                            if ($monnify_enabled || $wema_enabled)
                                $this->monnify_account($user->username);
                        } catch (\Exception $e) {
                            \Log::error("Register Monnify: " . $e->getMessage());
                        }

                        try {
                            $pointwave_enabled = $settings->pointwave_enabled ?? false;
                            if ($pointwave_enabled)
                                $this->pointwave_account($user->username);
                        } catch (\Exception $e) {
                            \Log::error("Register PointWave: " . $e->getMessage());
                        }

                        // if ($palmpay_enabled || $monnify_enabled)
                        //    $this->paymentpoint_account($user->username);
                        try {
                            $this->paystack_account($user->username);
                        } catch (\Exception $e) {
                            \Log::error("Register Paystack: " . $e->getMessage());
                        }
                        // Always try paystack or link to setting
                        $this->insert_stock($user->username);

                        $moniepoint_acc = optional(DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first())->account_number ?? null;

                        $user_details = [
                            'username' => $user->username,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'bal' => number_format($user->bal, 2),
                            'refbal' => number_format($user->refbal, 2),
                            'kyc' => $user->kyc,
                            'type' => $user->type,
                            'pin' => $user->pin,
                            'profile_image' => $user->profile_image,

                            // Conditionals (Safe Access)
                            'sterlen' => $moniepoint_acc,
                            'vdf' => (isset($user->vdf)) ? $user->vdf : null,
                            'fed' => (isset($user->fed)) ? $user->fed : null,
                            'wema' => (isset($user->wema)) ? $user->wema : null,
                            'opay' => $xixapay_enabled ? (isset($user->palmpay) ? $user->palmpay : null) : null,
                            'kolomoni_mfb' => $xixapay_enabled ? (isset($user->kolomoni_mfb) ? $user->kolomoni_mfb : null) : null,
                            'palmpay' => null,
                            'pointwave' => $pointwave_enabled ? (isset($user->pointwave_account_number) ? $user->pointwave_account_number : null) : null,
                            'pointwave_bank' => $pointwave_enabled ? (isset($user->pointwave_bank_name) ? $user->pointwave_bank_name : null) : null,

                            // Smart Fallback: If default provider account is missing, fallback to next available
                            'account_number' => ($active_default == 'pointwave' && $pointwave_enabled && isset($user->pointwave_account_number)) ? $user->pointwave_account_number :
                                (($active_default == 'xixapay' && $xixapay_enabled && isset($user->palmpay)) ? $user->palmpay :
                                    (($active_default == 'monnify' && $monnify_enabled && $moniepoint_acc) ? $moniepoint_acc :
                                        (($active_default == 'wema' && $wema_enabled && isset($user->paystack_account)) ? $user->paystack_account :
                                            // Fallback chain: PointWave PalmPay -> Xixapay PalmPay -> Kolomoni -> Wema -> Moniepoint
                                            (isset($user->pointwave_account_number) ? $user->pointwave_account_number :
                                                (isset($user->palmpay) ? $user->palmpay :
                                                    (isset($user->kolomoni_mfb) ? $user->kolomoni_mfb :
                                                        (isset($user->paystack_account) ? $user->paystack_account :
                                                            ($moniepoint_acc ?? null)))))))),

                            'bank_name' => ($active_default == 'pointwave' && $pointwave_enabled && isset($user->pointwave_bank_name)) ? $user->pointwave_bank_name :
                                (($active_default == 'xixapay' && $xixapay_enabled) ? 'PalmPay' :
                                    (($active_default == 'monnify' && $monnify_enabled) ? 'Moniepoint' :
                                        (($active_default == 'wema' && $wema_enabled) ? 'Wema Bank' :
                                            // Fallback chain: Use real bank names, never provider names
                                            (isset($user->pointwave_bank_name) ? $user->pointwave_bank_name :
                                                (isset($user->palmpay) ? 'PalmPay' :
                                                    (isset($user->kolomoni_mfb) ? 'Kolomoni MFB' :
                                                        (isset($user->paystack_account) ? 'Wema Bank' :
                                                            ($moniepoint_acc ? 'Moniepoint' : null)))))))),

                            'paystack_account' => $user->paystack_account,
                            'paystack_bank' => $user->paystack_bank,
                            'address' => $user->address,
                            'webhook' => $user->webhook,
                            'about' => $user->about,
                            'apikey' => $user->apikey,
                            'default_account' => $active_default,
                            'is_bvn' => $user->bvn == null ? false : true,
                            'theme' => DB::table('user_settings')->where('user_id', $user->id)->value('theme') ?? 'light'
                        ];

                        $token = $this->generatetoken($user->id);
                        $use = $this->core();
                        $general = $this->general();

                        // SKIP OTP - Auto-verify all users (both web and mobile)
                        DB::table('user')->where(['id' => $user->id])->update(['status' => 1]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Registration Successful',
                            'token' => $token,
                            'user' => $user_details
                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to Register User. Please try again later.',
                        ])->setStatusCode(403);
                    }
                }
        } else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function account(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $user_token = $request->id;
            $real_token = $this->verifyapptoken($user_token);
            if (!is_null($real_token)) {
                $habukhan_check = DB::table('user')->where('id', $real_token);
                if ($habukhan_check->count() == 1) {
                    $user = $habukhan_check->get()[0];
                    // Fetch settings to check enabled providers
                    try {
                        $settings = DB::table('settings')->select(
                            'palmpay_enabled',
                            'monnify_enabled',
                            'wema_enabled',
                            'xixapay_enabled',
                            'default_virtual_account'
                        )->first();

                        // Determine which accounts to show based on settings
                        // Monnify provides Sterling/Wema
                        $monnify_enabled = $settings->monnify_enabled ?? true;
                        // Wema is separate direct wema
                        $wema_enabled = $settings->wema_enabled ?? true;
                        // Xixapay provides OPay (kolomoni_mfb/opay columns)
                        $xixapay_enabled = $settings->xixapay_enabled ?? true;
                        // Palmpay is separate
                        $palmpay_enabled = $settings->palmpay_enabled ?? true;
                        // PointWave
                        $pointwave_enabled = $settings->pointwave_enabled ?? false;
                        $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                        $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                    } catch (\Exception $e) {
                        $monnify_enabled = true;
                        $wema_enabled = true;
                        $xixapay_enabled = true;
                        $palmpay_enabled = true;
                        $default_virtual_account = 'palmpay';
                    }

                    // Smart Fallback
                    $active_default = $default_virtual_account;
                    if ($active_default == 'wema' && !$wema_enabled)
                        $active_default = null;
                    if ($active_default == 'monnify' && !$monnify_enabled)
                        $active_default = null;
                    if ($active_default == 'xixapay' && !$xixapay_enabled)
                        $active_default = null;
                    if ($active_default == 'palmpay' && !$palmpay_enabled)
                        $active_default = null;
                    if ($active_default == 'pointwave' && !$pointwave_enabled)
                        $active_default = null;

                    if ($active_default == null) {
                        if ($palmpay_enabled)
                            $active_default = 'palmpay';
                        elseif ($wema_enabled)
                            $active_default = 'wema';
                        elseif ($monnify_enabled)
                            $active_default = 'monnify';
                        elseif ($xixapay_enabled)
                            $active_default = 'xixapay';
                    }

                    try {
                        if ($xixapay_enabled && $user->palmpay == null)
                            $this->xixapay_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Xixapay: " . $e->getMessage());
                    }

                    try {
                        if (($monnify_enabled || $wema_enabled) && ($user->paystack_account == null || DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->count() == 0))
                            $this->monnify_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Monnify: " . $e->getMessage());
                    }

                    try {
                        $pointwave_enabled = $settings->pointwave_enabled ?? false;
                        if ($pointwave_enabled && $user->pointwave_account_number == null)
                            $this->pointwave_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account PointWave: " . $e->getMessage());
                    }

                    try {
                        if ($palmpay_enabled && ($user->palmpay == null || $user->opay == null))
                            $this->paymentpoint_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account PaymentPoint: " . $e->getMessage());
                    }

                    try {
                        if ($user->paystack_account == null)
                            $this->paystack_account($user->username);
                    } catch (\Exception $e) {
                        \Log::error("Account Paystack: " . $e->getMessage());
                    }
                    // $this->insert_stock($user->username); // Optimize stock check if needed
                    $user = DB::table('user')->where(['id' => $user->id])->first();
                    $moniepoint_acc = optional(DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first())->account_number ?? null;

                    $user_details = [
                        'username' => $user->username,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'bal' => number_format($user->bal, 2),
                        'refbal' => number_format($user->refbal, 2),
                        'kyc' => $user->kyc,
                        'type' => $user->type,
                        'pin' => $user->pin,
                        'profile_image' => $user->profile_image,
                        // Only show if enabled
                        'sterlen' => $moniepoint_acc,
                        'fed' => null,
                        'wema' => $user->paystack_account,
                        'opay' => $xixapay_enabled ? $user->opay : null,
                        'kolomoni_mfb' => $user->kolomoni_mfb,
                        'palmpay' => $user->palmpay,
                        'pointwave' => $pointwave_enabled ? $user->pointwave_account_number : null,
                        'pointwave_bank' => $pointwave_enabled ? ($user->pointwave_bank_name ?? 'PointWave Bank') : null,

                        // Polyfill for Frontend 'Generating...' issue
                        'account_number' => ($monnify_enabled && !empty($moniepoint_acc)) ? $moniepoint_acc : (
                            ($active_default == 'wema') ? $user->paystack_account :
                            (($active_default == 'monnify') ? $moniepoint_acc :
                                (($active_default == 'xixapay') ? $user->palmpay :
                                    (($active_default == 'pointwave') ? $user->pointwave_account_number :
                                        null)))
                        ),

                        // Keep Paystack independent or link to another setting if needed
                        'bank_name' => ($monnify_enabled && !empty($moniepoint_acc)) ? 'Moniepoint' : (
                            ($active_default == 'wema') ? 'Wema Bank' :
                            (($active_default == 'monnify') ? 'Moniepoint' :
                                (($active_default == 'xixapay') ? 'PalmPay' :
                                    (($active_default == 'pointwave') ? ($user->pointwave_bank_name ?? 'PointWave Bank') :
                                        'PalmPay')))
                        ),

                        'paystack_account' => $user->paystack_account,
                        'paystack_bank' => $user->paystack_bank,
                        'kolomoni' => $user->kolomoni_mfb, // Alias for frontend
                        'vdf' => $user->vdf,
                        'address' => $user->address,
                        'webhook' => $user->webhook,
                        'about' => $user->about,
                        'apikey' => $user->apikey,
                        'default_account' => $active_default,
                        'is_bvn' => $user->bvn == null ? false : true,
                        'theme' => DB::table('user_settings')->where('user_id', $user->id)->value('theme') ?? 'light'
                    ];

                    if ($user->status == 0) {
                        // Auto-verify legacy unverified users (OTP disabled)
                        DB::table('user')->where(['id' => $user->id])->update(['status' => 1]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == 1) {
                        //set up the user over here


                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    } else if ($user->status == '2') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Banned'
                        ])->setStatusCode(403);
                    } elseif ($user->status == '3') {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Account Deactivated'
                        ])->setStatusCode(403);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to Get User'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Allowed',
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'AccessToken Expired'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',
            ])->setStatusCode(403);
        }
    }
    public function verify(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $habukhan_check = DB::table('user')->where('email', $request->email);
            if ($habukhan_check->count() == 1) {
                $user = $habukhan_check->get()[0];
                // FIX: Commenting out heavy external API calls
                // $this->xixapay_account($user->username);
                // $this->monnify_account($user->username);
                // $this->paymentpoint_account($user->username);
                // $this->paystack_account($user->username);
                // $this->insert_stock($user->username);
                $user = DB::table('user')->where(['id' => $user->id])->first();

                // Fetch settings to check enabled providers
                try {
                    $settings = DB::table('settings')->select(
                        'palmpay_enabled',
                        'monnify_enabled',
                        'wema_enabled',
                        'xixapay_enabled',
                        'default_virtual_account'
                    )->first();

                    // Determine which accounts to show based on settings
                    $monnify_enabled = $settings->monnify_enabled ?? true;
                    $wema_enabled = $settings->wema_enabled ?? true;
                    $xixapay_enabled = $settings->xixapay_enabled ?? true;
                    $palmpay_enabled = $settings->palmpay_enabled ?? true;
                    $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                } catch (\Exception $e) {
                    $monnify_enabled = true;
                    $wema_enabled = true;
                    $xixapay_enabled = true;
                    $palmpay_enabled = true;
                    $default_virtual_account = 'palmpay';
                }

                // Smart Fallback
                $active_default = $default_virtual_account;
                if ($active_default == 'wema' && !$wema_enabled)
                    $active_default = null;
                if ($active_default == 'monnify' && !$monnify_enabled)
                    $active_default = null;
                if ($active_default == 'xixapay' && !$xixapay_enabled)
                    $active_default = null;
                if ($active_default == 'palmpay' && !$palmpay_enabled)
                    $active_default = null;

                if ($active_default == null) {
                    if ($palmpay_enabled)
                        $active_default = 'palmpay';
                    elseif ($wema_enabled)
                        $active_default = 'wema';
                    elseif ($monnify_enabled)
                        $active_default = 'monnify';
                    elseif ($xixapay_enabled)
                        $active_default = 'xixapay';
                }



                $moniepoint_acc = optional(DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first())->account_number ?? null;

                $user_details = [
                    'username' => $user->username,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'bal' => number_format($user->bal, 2),
                    'refbal' => number_format($user->refbal, 2),
                    'kyc' => $user->kyc,
                    'type' => $user->type,
                    'pin' => $user->pin,
                    'profile_image' => $user->profile_image,

                    // Conditionals (Safe Access)
                    'sterlen' => $moniepoint_acc,
                    'fed' => (isset($user->fed)) ? $user->fed : null,
                    'wema' => (isset($user->wema)) ? $user->wema : null,
                    'opay' => $xixapay_enabled ? (isset($user->opay) ? $user->opay : null) : null,
                    'kolomoni_mfb' => $xixapay_enabled ? (isset($user->kolomoni_mfb) ? $user->kolomoni_mfb : null) : null,
                    'palmpay' => $palmpay_enabled ? (isset($user->palmpay) ? $user->palmpay : null) : null,

                    'paystack_account' => $user->paystack_account,
                    'paystack_bank' => $user->paystack_bank,
                    'vdf' => $user->vdf,
                    'address' => $user->address,
                    'webhook' => $user->webhook,
                    'about' => $user->about,
                    'apikey' => $user->apikey,
                    'default_account' => $active_default,
                    'account_name' => isset($user->account_name) ? $user->account_name : null,
                    'is_bvn' => $user->bvn == null ? false : true,
                    'theme' => DB::table('user_settings')->where('user_id', $user->id)->value('theme') ?? 'light'
                ];
                if ($user->otp == $request->code) {
                    //if success
                    $data = [
                        'status' => '1',
                        'otp' => null
                    ];
                    $tableid = [
                        'id' => $user->id
                    ];
                    $general = $this->general();
                    $this->updateData($data, 'user', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'WELCOME EMAIL',
                        'sender_mail' => $general->app_email,
                        'system_email' => $general->app_email,
                        'app_name' => $general->app_name,
                        'pin' => $user->pin,
                    ];
                    // FIX: Disable Mail to prevent timeout
                    // MailController::send_mail($email_data, 'email.welcome');
                    return response()->json([
                        'status' => 'success',
                        'message' => 'account verified',
                        'user' => $user_details,
                        'token' => $this->generatetoken($user->id)
                    ]);
                } else {
                    // Fix for Connection Error/Timeout Retry Issue
                    // If the previous request succeeded in DB but failed to return response (timeout),
                    // the user is already verified (status == 1) but OTP is null.
                    // We should allow them to proceed.
                    if ($user->status == 1) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'account verified',
                            'user' => $user_details
                        ]);
                    }

                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid OTP'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to verify user'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System',

            ])->setStatusCode(403);
        }
    }
    public function login(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            try {
                //our login function over here
                \Log::info('API Login Hit: ' . json_encode($request->except('password')));
                $validator = Validator::make($request->all(), [
                    'username' => 'required|string',
                    'password' => 'required'
                ], [
                    'username.required' => 'Your Username or Phone Number or Email is Required',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 403,
                        'message' => $validator->errors()->first()
                    ])->setStatusCode(403);
                } else {
                    $check_system = User::where('username', $request->username);
                    if ($check_system->count() == 1) {
                        $user = $check_system->get()[0];
                        // Fetch settings to check enabled providers
                        try {
                            $settings = DB::table('settings')->select(
                                'palmpay_enabled',
                                'monnify_enabled',
                                'wema_enabled',
                                'xixapay_enabled',
                                'pointwave_enabled',
                                'default_virtual_account'
                            )->first();

                            // Determine which accounts to show based on settings
                            $monnify_enabled = $settings->monnify_enabled ?? true;
                            $wema_enabled = $settings->wema_enabled ?? true;
                            $xixapay_enabled = $settings->xixapay_enabled ?? true;
                            $palmpay_enabled = $settings->palmpay_enabled ?? true;
                            $pointwave_enabled = $settings->pointwave_enabled ?? false;
                            $default_virtual_account = $settings->default_virtual_account ?? 'palmpay';
                            $default_virtual_account = ($default_virtual_account == 'palmpay') ? 'xixapay' : $default_virtual_account; // Migration for name change if needed
                        } catch (\Exception $e) {
                            $monnify_enabled = true;
                            $wema_enabled = true;
                            $xixapay_enabled = true;
                            $palmpay_enabled = true;
                            $pointwave_enabled = false;
                            $default_virtual_account = 'palmpay';
                        }

                        // Smart Fallback
                        $active_default = $default_virtual_account;
                        if ($active_default == 'wema' && !$wema_enabled)
                            $active_default = null;
                        if ($active_default == 'monnify' && !$monnify_enabled)
                            $active_default = null;
                        if ($active_default == 'xixapay' && !$xixapay_enabled)
                            $active_default = null;
                        if ($active_default == 'palmpay' && !$palmpay_enabled)
                            $active_default = null;
                        if ($active_default == 'pointwave' && !$pointwave_enabled)
                            $active_default = null;

                        if ($active_default == null) {
                            if ($pointwave_enabled)
                                $active_default = 'pointwave';
                            elseif ($palmpay_enabled)
                                $active_default = 'palmpay';
                            elseif ($wema_enabled)
                                $active_default = 'wema';
                            elseif ($monnify_enabled)
                                $active_default = 'moniepoint';
                            elseif ($xixapay_enabled)
                                $active_default = 'xixapay';
                        }


                        // Auto-create PointWave account if enabled and missing
                        try {
                            if ($pointwave_enabled && empty($user->pointwave_account_number)) {
                                $this->pointwave_account($user->username);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Login PointWave: " . $e->getMessage());
                        }

                        // Auto-create Xixapay accounts if enabled and missing
                        try {
                            if ($xixapay_enabled && (empty($user->palmpay) || empty($user->kolomoni_mfb))) {
                                $this->xixapay_account($user->username);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Login Xixapay: " . $e->getMessage());
                        }

                        // Refresh user data after account creation
                        $user = DB::table('user')->where(['id' => $user->id])->first();
                        $moniepoint_row = DB::table('user_bank')->where(['username' => $user->username, 'bank' => 'MONIEPOINT'])->first();
                        $moniepoint_acc = $moniepoint_row ? $moniepoint_row->account_number : null;

                        $user_details = [
                            'username' => $user->username,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'bal' => number_format($user->bal, 2),
                            'refbal' => number_format($user->refbal, 2),
                            'kyc' => $user->kyc,
                            'type' => $user->type,
                            'pin' => $user->pin,
                            'profile_image' => $user->profile_image,

                            // Conditionals
                            'sterlen' => $moniepoint_acc,
                            'fed' => null,
                            'wema' => $user->paystack_account,
                            'opay' => $xixapay_enabled ? $user->palmpay : null,
                            'kolomoni_mfb' => $xixapay_enabled ? $user->kolomoni_mfb : null,
                            'palmpay' => null,
                            'pointwave' => $pointwave_enabled ? $user->pointwave_account_number : null,
                            'pointwave_bank' => $pointwave_enabled ? $user->pointwave_bank_name : null,

                            // Smart Fallback: If default provider account is missing, fallback to next available
                            'account_number' => ($active_default == 'pointwave' && $pointwave_enabled && !empty($user->pointwave_account_number)) ? $user->pointwave_account_number :
                                (($active_default == 'xixapay' && $xixapay_enabled && !empty($user->palmpay)) ? $user->palmpay :
                                    (($active_default == 'monnify' && $monnify_enabled && !empty($moniepoint_acc)) ? $moniepoint_acc :
                                        (($active_default == 'wema' && $wema_enabled && !empty($user->paystack_account)) ? $user->paystack_account :
                                            // Fallback chain: PointWave PalmPay -> Xixapay PalmPay -> Kolomoni -> Wema -> Moniepoint
                                            (!empty($user->pointwave_account_number) ? $user->pointwave_account_number :
                                                (!empty($user->palmpay) ? $user->palmpay :
                                                    (!empty($user->kolomoni_mfb) ? $user->kolomoni_mfb :
                                                        (!empty($user->paystack_account) ? $user->paystack_account :
                                                            ($moniepoint_acc ?? null)))))))),

            // Keep Paystack independent or link to another setting if needed
                            'bank_name' => ($active_default == 'pointwave' && $pointwave_enabled && !empty($user->pointwave_bank_name)) ? $user->pointwave_bank_name :
                                (($active_default == 'xixapay' && $xixapay_enabled) ? 'PalmPay' :
                                    (($active_default == 'monnify' && $monnify_enabled) ? 'Moniepoint' :
                                        (($active_default == 'wema' && $wema_enabled) ? 'Wema Bank' :
                                            // Fallback chain: Use real bank names, never provider names
                                            (!empty($user->pointwave_bank_name) ? $user->pointwave_bank_name :
                                                (!empty($user->palmpay) ? 'PalmPay' :
                                                    (!empty($user->kolomoni_mfb) ? 'Kolomoni MFB' :
                                                        (!empty($user->paystack_account) ? 'Wema Bank' :
                                                            ($moniepoint_acc ? 'Moniepoint' : 'PalmPay')))))))),

                            'paystack_account' => $user->paystack_account,
                            'paystack_bank' => $user->paystack_bank,
                            'kolomoni' => $user->kolomoni_mfb, // Alias for frontend
                            'vdf' => $user->vdf,
                            'address' => $user->address,
                            'webhook' => $user->webhook,
                            'about' => $user->about,
                            'apikey' => $user->apikey,
                            'default_account' => $active_default,
                            'account_name' => isset($user->account_name) ? $user->account_name : null,
                            'is_bvn' => $user->bvn == null ? false : true,
                            'theme' => DB::table('user_settings')->where('user_id', $user->id)->value('theme') ?? 'light'
                        ];
                        $hash = substr(sha1(md5($request->password)), 3, 10);
                        $mdpass = md5($request->password);
                        $is_bcrypt_match = password_verify($request->password, $user->password);
                        $is_plain_match = ($request->password == $user->password);
                        $is_legacy_hash_match = ($hash == $user->password);
                        $is_md5_match = ($mdpass == $user->password);

                        // Debug Log (Optimized: Uses cached values, prevents 2x Cost 16 calculation)
                        \Log::info('Login Debug: User=' . $user->username . ', Type="' . $user->type . '", Status=' . $user->status . ', PlainMatch=' . ($is_plain_match ? 'YES' : 'NO') . ', HashMatch=' . ($is_bcrypt_match ? 'YES' : 'NO'));

                        // FIX: Replaced XOR chain with simple OR. If ANY credential match is valid, let them in.
                        if ($is_bcrypt_match || $is_plain_match || $is_legacy_hash_match || $is_md5_match) {

                            if ($user->status == 1 || trim(strtoupper($user->type)) == 'ADMIN' || strcasecmp($user->username, 'Habukhan') == 0) {
                                \Log::info('Login Success Response for ' . $user->username . ': ' . json_encode([
                                    'pointwave' => $user_details['pointwave'],
                                    'pointwave_bank' => $user_details['pointwave_bank'],
                                    'palmpay' => $user_details['opay'],
                                    'kolomoni_mfb' => $user_details['kolomoni_mfb'],
                                    'wema' => $user_details['wema'],
                                    'account_number' => $user_details['account_number'],
                                    'bank_name' => $user_details['bank_name'],
                                    'default_account' => $user_details['default_account']
                                ]));
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Login successfully',
                                    'user' => $user_details,
                                    'token' => $this->generatetoken($user->id)
                                ]);
                            } else if ($user->status == 2) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => $user->username . ' Your Account Has Been Banned'
                                ])->setStatusCode(403);
                            } else if ($user->status == 3) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => $user->username . ' Your Account Has Been Deactivated'
                                ])->setStatusCode(403);
                            } else if ($user->status == 0) {
                                // SKIP OTP - Auto-verify and login for all users
                                DB::table('user')->where(['id' => $user->id])->update(['status' => 1]);
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Login successfully',
                                    'user' => $user_details,
                                    'token' => $this->generatetoken($user->id)
                                ]);
                            } else {
                                \Log::warning('Login Failed: Status logic mismatch for User=' . $user->username . ', Status=' . $user->status);
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'System is unable to verify user'

                                ])->setStatusCode(403);
                            }
                        } else {
                            \Log::warning('Login Failed: Password mismatch for User=' . $user->username);
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Password Note Password is Case Sensitive'
                            ])->setStatusCode(403);
                        }
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Username and Password.'
                        ])->setStatusCode(403);
                    }
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Server Crash: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => substr($e->getTraceAsString(), 0, 500)
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Origin validation failed. Please check your .env configuration.',
                'origin' => $request->headers->get('origin'),
                'allowed' => explode(',', config('app.habukhan_app_key'))
            ], 403);
        }
    }

    public function resendOtp(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (isset($request->id)) {
                $sel_user = DB::table('user')->where('email', $request->id);
                if ($sel_user->count() == 1) {
                    $user = $sel_user->get()[0];
                    $general = $this->general();
                    $otp = random_int(100000, 999999);
                    $data = [
                        'otp' => $otp
                    ];
                    $tableid = [
                        'username' => $user->username
                    ];
                    $this->updateData($data, 'user', $tableid);
                    $email_data = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'title' => 'Account Verification',
                        'sender_mail' => $general->app_email,
                        'app_name' => config('app.name'),
                        'otp' => $otp
                    ];
                    MailController::send_mail($email_data, 'email.verify');
                    return response()->json([
                        'status' => 'status',
                        'message' => 'New OTP Resent to Your Email'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to Detect User'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'An Error Occurred'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
        }
    }

    /**
     * Set/Update Transaction PIN
     */
    public function createPin(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (strpos($authHeader, 'Bearer ') === 0) {
            $authHeader = substr($authHeader, 7);
        }

        // Identify user by token (habukhan_key)
        $user = DB::table('user')->where('habukhan_key', $authHeader)->first();

        if ($user) {
            $validator = Validator::make($request->all(), [
                'pin' => 'required|numeric|digits:4',
                'confirm_pin' => 'required|same:pin',
            ], [
                'pin.required' => 'Transaction PIN is required',
                'pin.digits' => 'PIN must be 4 digits',
                'confirm_pin.same' => 'PINs do not match',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 403,
                    'message' => $validator->errors()->first()
                ])->setStatusCode(403);
            }

            DB::table('user')->where('id', $user->id)->update([
                'pin' => $request->pin,
                'status' => 1 // Ensure user is active if they reached this step
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction PIN created successfully'
            ]);
        }

        return response()->json([
            'status' => 403,
            'message' => 'Unauthorized or Session Expired'
        ])->setStatusCode(403);
    }
    /**
     * Check if a system feature is locked (Admin/Internal use)
     */
    public function CheckSystemLock($feature)
    {
        // Special Handling for Airtime to Cash (Check network availability)
        if ($feature === 'airtime_to_cash') {
            $availableNetworks = DB::table('network')->where('cash', 1)->count();
            return response()->json([
                'status' => 'success',
                'message' => 'A2C Lock status retrieved from network table',
                'data' => ['is_locked' => ($availableNetworks === 0)]
            ]);
        }

        $lock = DB::table('system_locks')->where('feature_key', $feature)->first();

        if (!$lock) {
            return response()->json([
                'status' => 'success',
                'message' => 'Feature not found, assuming unlocked',
                'data' => ['is_locked' => false]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lock status retrieved',
            'data' => ['is_locked' => (bool) $lock->is_locked]
        ]);
    }

    /**
     * Phase 2: KYC Verification
     */
    public function verifyKyc(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'id_type' => 'required|in:bvn,nin',
            'id_number' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = DB::table('user')->where('id', $request->user()->id ?? 0)->first();
        if (!$user) {
            // Fallback for tokenauth if $request->user() is null (depends on middleware)
            // But auth middleware should handle it.
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        // 2. Check for Duplicates in user_kyc
        // We assume id_number is stored plain or consistent hash. 
        // Logic: if someone else verified this ID, block.
        $existing = DB::table('user_kyc')
            ->where('id_type', $request->id_type)
            ->where('id_number', $request->id_number) // In PROD, might want to hash this for privacy, but for now matching requirements
            ->where('status', 'verified')
            ->first();

        if ($existing) {
            // Allow re-verification if it's the SAME user (e.g. retry or lost status)
            if ($existing->user_id == $user->id) {
                // Already verified, just return success
                return response()->json([
                    'status' => 'success',
                    'message' => 'Identity already verified',
                    'data' => json_decode($existing->full_response_json, true)
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'This ID is already linked to another account.'
            ], 409);
        }

        // 3. Call Provider
        try {
            $provider = new \App\Services\Banking\Providers\XixapayProvider();
            $result = $provider->verifyIdentity($request->id_type, $request->id_number);

            if ($result['status'] === 'success') {
                // 4. Success - Save to DB
                DB::table('user_kyc')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'id_type' => $request->id_type
                    ],
                    [
                        'id_number' => $request->id_number,
                        'full_response_json' => json_encode($result['full_response']),
                        'provider' => 'xixapay',
                        'status' => 'verified',
                        'verified_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                // Update User Table
                $updateData = ['kyc' => '1']; // Or 'verified'
                if ($request->id_type === 'bvn') {
                    $updateData['bvn'] = $request->id_number;
                }
                if ($request->id_type === 'nin') {
                    $updateData['nin'] = $request->id_number;
                }

                DB::table('user')->where('id', $user->id)->update($updateData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Identity Verification Successful',
                    'data' => $result['data']
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            \Log::error("KYC Verification Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Verification Service Unavailable'
            ], 500);
        }
    }

    /**
     * Get KYC Details
     */
    public function getKycDetails(Request $request)
    {
        $user = DB::table('user')->where('id', $request->user()->id ?? 0)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);
        }

        $kyc = DB::table('user_kyc')->where('user_id', $user->id)->first();

        if ($kyc) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => $kyc->status,
                    'id_type' => $kyc->id_type,
                    'verified_at' => $kyc->verified_at,
                    'details' => json_decode($kyc->full_response_json, true)
                ]
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => 'KYC not verified',
            'data' => null
        ]);
    }
    /**
     * Phase 3: Create Customer
     */
    public function createCustomer(Request $request)
    {
        $user = DB::table('user')->where('id', $request->user()->id ?? 0)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        // 1. Check if already exists
        if (!empty($user->customer_id)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Customer already created',
                'customer_id' => $user->customer_id
            ]);
        }

        // 2. Check KYC
        $kyc = DB::table('user_kyc')->where('user_id', $user->id)->where('status', 'verified')->first();
        if (!$kyc) {
            return response()->json(['status' => 'error', 'message' => 'Account Verification (KYC) Required'], 403);
        }

        // 3. Validation
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'id_card' => 'required|file|mimes:jpeg,png,pdf|max:5120', // Max 5MB
            'utility_bill' => 'required|file|mimes:jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 4. Prepare Payload
        $nameParts = explode(' ', $user->name, 2);
        $first_name = $nameParts[0];
        $last_name = $nameParts[1] ?? $first_name;

        $payload = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'id_type' => $kyc->id_type,
            'id_number' => $kyc->id_number,
            // New Fields
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'date_of_birth' => $request->date_of_birth,
            // Files
            'id_card' => $request->file('id_card'),
            'utility_bill' => $request->file('utility_bill')
        ];

        // 5. API Call
        try {
            $provider = new \App\Services\Banking\Providers\XixapayProvider();
            $result = $provider->createCustomer($payload);

            if ($result['status'] === 'success') {
                // 6. Save
                DB::table('user')->where('id', $user->id)->update([
                    'customer_id' => $result['customer_id'],
                    'customer_data' => json_encode($result['full_response']),
                    // store address in user table?
                    'address' => $request->address . ', ' . $request->city . ', ' . $request->state
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer Created Successfully',
                    'customer_id' => $result['customer_id']
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);

        } catch (\Exception $e) {
            \Log::error("Customer Creation Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }

    /**
     * Update Customer Details (Phase 3 Extra)
     */
    public function updateCustomer(Request $request)
    {
        $user = DB::table('user')->where('id', $request->user()->id ?? 0)->first();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User not found'], 401);

        if (empty($user->customer_id)) {
            return response()->json(['status' => 'error', 'message' => 'Customer not found. Create one first.'], 404);
        }

        // Validation - same as create
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'postal_code' => 'required|string',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'id_card' => 'nullable|file|mimes:jpeg,png,pdf|max:5120', // Optional on update
            'utility_bill' => 'nullable|file|mimes:jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // Get KYC Data for ID info
        $kyc = DB::table('user_kyc')->where('user_id', $user->id)->where('status', 'verified')->first();
        if (!$kyc) {
            return response()->json(['status' => 'error', 'message' => 'Valid KYC needed'], 403);
        }

        $nameParts = explode(' ', $user->name, 2);
        $first_name = $nameParts[0];
        $last_name = $nameParts[1] ?? $first_name;

        $payload = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $user->email,
            'phone_number' => $user->phone,
            'id_type' => $kyc->id_type,
            'id_number' => $kyc->id_number,
            'address' => $request->address,
            'state' => $request->state,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'date_of_birth' => $request->date_of_birth,
        ];

        if ($request->hasFile('id_card')) {
            $payload['id_card'] = $request->file('id_card');
        }
        if ($request->hasFile('utility_bill')) {
            $payload['utility_bill'] = $request->file('utility_bill');
        }

        try {
            $provider = new \App\Services\Banking\Providers\XixapayProvider();
            $result = $provider->updateCustomer($payload);

            if ($result['status'] === 'success') {
                DB::table('user')->where('id', $user->id)->update([
                    'customer_data' => json_encode($result['full_response']),
                    'address' => $request->address . ', ' . $request->city . ', ' . $request->state
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer Updated Successfully',
                    'customer_id' => $result['customer_id']
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);

        } catch (\Exception $e) {
            \Log::error("Customer Update Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }



    /**
     * Update Virtual Account Status
     * PATCH /api/user/virtual-account/status
     */
    public function updateVirtualAccountStatus(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'accountNumber' => 'required|string',
            'status' => 'required|in:active,deactivated',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 2. Call Provider
        try {
            $provider = new \App\Services\Banking\Providers\XixapayProvider();
            $result = $provider->updateVirtualAccountStatus(
                $request->accountNumber,
                $request->status,
                $request->reason
            );

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            }

            return response()->json(['status' => 'error', 'message' => $result['message']], 400);

        } catch (\Exception $e) {
            \Log::error("Update VA Status Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Service Unavailable'], 500);
        }
    }
}