<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppController extends Controller
{
    public function system(Request $request)
    {
        try {
            $explode_url = explode(',', config('app.habukhan_app_key'));
            $origin = $request->headers->get('origin');
            if (!$origin || in_array($origin, $explode_url)) {
                return response()->json([
                    'status' => 'success',
                    'setting' => $this->core(),
                    'feature' => $this->feature(),
                    'general' => $this->general(),
                    'bank' => DB::table('habukhan_key')->select('account_number', 'account_name', 'bank_name', 'min', 'max')->first(),
                    'support' => [
                        'support_ai_name' => 'Amtpay',
                        'support_call_name' => 'Aminiya',
                        'support_phone' => optional($this->general())->app_phone ?? '+2349137570018',
                        'support_whatsapp' => '2349137570018'
                    ]
                ]);
            }
            else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Origin validation failed. Please check your .env configuration.',
                    'origin' => $origin,
                    'allowed' => $explode_url
                ], 403);
            }
        }
        catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'System Crash: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function welcomeMessage()
    {
        $settings = $this->core();
        return response()->json([
            'status' => 'success',
            'notif_message' => $settings->notif_message ?? '',
            'notif_show' => $settings->notif_show ?? 0,
            'ads_message' => $settings->ads_message ?? '',
            'ads_show' => $settings->ads_show ?? 0,
            'app_notif_show' => $settings->app_notif_show ?? 1
        ]);
    }

    public function getDiscountOther(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->select(
                'monnify_charge', 
                'xixapay_charge', 
                'paystack_charge',
                'pointwave_charge_type',
                'pointwave_charge_value',
                'pointwave_charge_cap'
            )->first();
            $cardSettings = DB::table('card_settings')->where('id', 1)->first();

            return response()->json([
                'status' => 'success',
                'monnify_charge' => $settings->monnify_charge ?? '0',
                'xixapay_charge' => $settings->xixapay_charge ?? '0',
                'glode_charge' => $settings->xixapay_charge ?? '0',
                'paystack_charge' => $settings->paystack_charge ?? '0',
                'pointwave_charge_type' => $settings->pointwave_charge_type ?? 'FLAT',
                'pointwave_charge_value' => $settings->pointwave_charge_value ?? '0.00',
                'pointwave_charge_cap' => $settings->pointwave_charge_cap ?? '0.00',
                'vcard_ngn_fee' => $cardSettings->ngn_creation_fee ?? 500,
                'vcard_usd_fee' => $cardSettings->usd_creation_fee ?? 3,
                'vcard_usd_rate' => $cardSettings->ngn_rate ?? 1600,
                'vcard_fund_fee' => $cardSettings->funding_fee_percent ?? 1,
                'vcard_usd_failed_fee' => $cardSettings->usd_failed_tx_fee ?? 0.4,
                'vcard_ngn_fund_fee' => $cardSettings->ngn_funding_fee_percent ?? 2,
                'vcard_usd_fund_fee' => $cardSettings->usd_funding_fee_percent ?? 2,
                'vcard_ngn_failed_fee' => $cardSettings->ngn_failed_tx_fee ?? 0,
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getDiscountSystem(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->first();
            return response()->json([
                'status' => 'success',
                'version' => $settings->version ?? '1.0.0',
                'update_url' => $settings->update_url ?? '',
                'playstore_url' => $settings->playstore_url ?? '',
                'appstore_url' => $settings->appstore_url ?? '',
                'update_title' => $settings->app_update_title ?? '',
                'update_desc' => $settings->app_update_desc ?? '',
                'maintenance' => (bool)($settings->maintenance ?? false),
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getDiscountBanks(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->select(
                'transfer_charge_type',
                'transfer_charge_value',
                'transfer_charge_cap'
            )->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transfer_charge_type' => $settings->transfer_charge_type ?? 'FLAT',
                    'transfer_charge_value' => (float)($settings->transfer_charge_value ?? 0),
                    'transfer_charge_cap' => (float)($settings->transfer_charge_cap ?? 0),
                ]
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getVirtualAccountStatus(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $settings = DB::table('settings')->select(
                'palmpay_enabled',
                'monnify_enabled',
                'wema_enabled',
                'xixapay_enabled',
                'pointwave_enabled',
                'default_virtual_account',
                'transfer_lock_all'
            )->first();

            return response()->json([
                'status' => 'success',
                'providers' => [
                    'palmpay' => [
                        'enabled' => (bool)($settings->palmpay_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'palmpay'
                    ],
                    'monnify' => [
                        'enabled' => (bool)($settings->monnify_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'monnify'
                    ],
                    'wema' => [
                        'enabled' => (bool)($settings->wema_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'wema'
                    ],
                    'xixapay' => [
                        'enabled' => (bool)($settings->xixapay_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'xixapay'
                    ],
                    'pointwave' => [
                        'enabled' => (bool)($settings->pointwave_enabled ?? true),
                        'is_default' => ($settings->default_virtual_account ?? 'palmpay') === 'pointwave'
                    ]
                ],
                'default_provider' => $settings->default_virtual_account ?? 'palmpay',
                'transfer_lock_all' => (bool)($settings->transfer_lock_all ?? false)
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function getDiscountCash(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $discount = DB::table('cash_discount')->first();
            return response()->json([
                'status' => 'success',
                'cash' => [
                    'mtn' => $discount->mtn ?? 80,
                    'glo' => $discount->glo ?? 70,
                    'airtel' => $discount->airtel ?? 70,
                    'mobile' => $discount->mobile ?? 70,
                    'mtn_status' => $discount->mtn_status ?? 1,
                    'glo_status' => $discount->glo_status ?? 1,
                    'airtel_status' => $discount->airtel_status ?? 1,
                    'mobile_status' => $discount->mobile_status ?? 1,
                ]
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }


    public function apiUpgrade(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'username' => 'required|max:25',
                'url' => 'required|url',
            ], [
                'url.url' => 'Invalid URL it must contain (https or www)',
                'url.required' => 'Your website URL is Needed For Verification'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            }
            else {
                //api user dat need upgrade
                $check_me = [
                    'username' => $request->username,
                    'status' => 1
                ];
                $check_user = DB::table('user')->where($check_me);
                if ($check_user->count() == 1) {
                    $user = $check_user->get()[0];
                    $general = $this->general();
                    $date = $this->system_date();
                    $ref = $this->generate_ref('API_UPGRADE');
                    $get_admins = DB::table('user')->where('status', '1')->where(function ($query) {
                        $query->where('type', 'ADMIN')
                            ->orWhere('type', 'CUSTOMER');
                    });
                    if ($get_admins->count() > 0) {
                        foreach ($get_admins->get() as $send_admin) {
                            $email_data = [
                                'name' => $user->name,
                                'email' => $send_admin->email,
                                'username' => $user->username,
                                'title' => 'API PACKAGE REQUEST',
                                'sender_mail' => $general->app_email,
                                'user_email' => $user->email,
                                'app_name' => $general->app_name,
                                'website' => $request->url,
                                'date' => $date,
                                'transid' => $ref,
                                'app_phone' => $general->app_phone
                            ];
                            MailController::send_mail($email_data, 'email.apirequest');
                        }
                        $insert_data = [
                            'username' => $user->username,
                            'date' => $date,
                            'transid' => $ref,
                            'status' => 0,
                            'title' => 'API UPGRAGE',
                            'message' => $user->username . ', want is account to be upgraded to API Package and is website url is ' . $request->url
                        ];
                        $insert = $this->inserting_data('request', $insert_data);
                        if ($insert) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Your Request has been received and it will be processed within 3-5 days'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'System is unable to send request now',
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Unable to get Admins',
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }



    public function buildWebsite(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $validator = validator::make($request->all(), [
                'username' => 'required|max:25',
                'url' => 'required|url',
            ], [
                'url.url' => 'Invalid URL it must contain (https or www)',
                'url.required' => 'Your website URL is Needed For Verification'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'status' => 403
                ])->setStatusCode(403);
            }
            else {
                $check_me = [
                    'username' => $request->username,
                    'status' => 1
                ];
                $check_user = DB::table('user')->where($check_me);
                if ($check_user->count() == 1) {
                    $user = $check_user->get()[0];
                    $general = $this->general();
                    $date = $this->system_date();
                    $setting = $this->core();
                    $ref = $this->generate_ref('AFFLIATE_WEBSITE');
                    if (!empty($setting->affliate_price)) {
                        if ($user->bal > $setting->affliate_price) {
                            $verify = DB::table('message')->where('transid', $ref);
                            if ($verify->count() == 0) {
                                $check_request = DB::table('request')->where('transid', $ref);
                                if ($check_request->count() == 0) {
                                    $debit_user = $user->bal - $setting->affliate_price;
                                    $data = [
                                        'bal' => $debit_user,
                                    ];
                                    $where_user = [
                                        'username' => $user->username,
                                        'id' => $user->id
                                    ];
                                    $update_user = $this->updateData($data, 'user', $where_user);
                                    if ($update_user) {
                                        $insert_message = [
                                            'username' => $user->username,
                                            'amount' => $setting->affliate_price,
                                            'message' => 'Purchase An Affliate Website',
                                            'oldbal' => $user->bal,
                                            'newbal' => $debit_user,
                                            'habukhan_date' => $date,
                                            'transid' => $ref,
                                            'plan_status' => 1,
                                            'role' => 'WEBSITE'
                                        ];
                                        $this->inserting_data('message', $insert_message);
                                        $get_admins = DB::table('user')->where('status', '1')->where(function ($query) {
                                            $query->where('type', 'ADMIN')
                                                ->orWhere('type', 'CUSTOMER');
                                        });
                                        if ($get_admins->count() > 0) {
                                            foreach ($get_admins->get() as $send_admin) {
                                                $email_data = [
                                                    'name' => $user->name,
                                                    'email' => $send_admin->email,
                                                    'username' => $user->username,
                                                    'title' => 'AFFLIATE WEBSITE',
                                                    'sender_mail' => $general->app_email,
                                                    'user_email' => $user->email,
                                                    'app_name' => $general->app_name,
                                                    'website' => $request->url,
                                                    'date' => $date,
                                                    'transid' => $ref,
                                                    'app_phone' => $general->app_phone
                                                ];
                                                MailController::send_mail($email_data, 'email.affliate_request');
                                            }
                                            $insert_data = [
                                                'username' => $user->username,
                                                'date' => $date,
                                                'transid' => $ref,
                                                'status' => 0,
                                                'title' => 'AFFLIATE WEBSITE',
                                                'message' => $user->username . ', want to make an affliate website. Domain Url is (Account Debited)' . $request->url
                                            ];
                                            $insert = $this->inserting_data('request', $insert_data);
                                            if ($insert) {
                                                return response()->json([
                                                    'status' => 'success',
                                                    'message' => 'Your Request has been received and it will be processed within 3-5 days',
                                                ]);
                                            }
                                            else {
                                                return response()->json([
                                                    'status' => 403,
                                                    'message' => 'System is unable to send request now',
                                                ])->setStatusCode(403);
                                            }
                                        }
                                        else {
                                            return response()->json([
                                                'status' => 403,
                                                'message' => 'Unable to get Admins',
                                            ])->setStatusCode(403);
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Service Currently Not Avialable For You Right Now'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Please Try Again After Few Mins'
                                    ]);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Please Try Again After Few Mins'
                                ]);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account Fund Your Wallet And Try Again ~ ₦' . number_format($user->bal, 2)
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System Is Unable to Detect Price'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AwufPackage(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!isset($request->id)) {
                return response()->json([
                    'message' => 'User ID Required',
                    'status' => 403
                ])->setStatusCode(403);
            }
            else {
                $check_me = [
                    'username' => $request->id,
                    'status' => 1
                ];
                $check_user = DB::table('user')->where($check_me);
                if ($check_user->count() == 1) {
                    $setting = $this->core();
                    $user = $check_user->first();
                    $ref = $this->generate_ref('AWUF_PACKAGE');
                    $date = $this->system_date();
                    if (!empty($setting->awuf_price)) {
                        if ($user->bal > $setting->awuf_price) {
                            $debit_user = $user->bal - $setting->awuf_price;
                            $credit_user = $debit_user + $setting->awuf_price;
                            if ($this->updateData(['bal' => $debit_user], 'user', ['username' => $user->username, 'id' => $user->id])) {
                                if (DB::table('message')->where('transid', $ref)->count() == 0) {
                                    $data = [
                                        'username' => $user->username,
                                        'amount' => $setting->awuf_price,
                                        'habukhan_date' => $date,
                                        'transid' => $ref,
                                        'plan_status' => 1,
                                        'newbal' => $debit_user,
                                        'oldbal' => $user->bal,
                                        'message' => 'Successfully Upgraded Your Account To AWUF PACKAGE',
                                        'role' => 'UPGRADE'
                                    ];
                                    if ($this->inserting_data('message', $data)) {
                                        $this->updateData(['type' => 'AWUF'], 'user', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Account Upgraded To AWUF PACKAGE Successfully'
                                        ]);
                                    }
                                    else {
                                        $this->updateData(['bal' => $credit_user], 'user', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Try Again Later'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    $this->updateData(['bal' => $credit_user], 'user', ['username' => $user->username, 'id' => $user->id]);
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Try Again Later'
                                    ])->setStatusCode(403);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'System Unavialable Right Now'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account, Fund Your Wallet And Try Again ~ ₦' . number_format($user->bal, 2)
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System is unable to Detect Price Right Now'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }

    //agent package

    public function AgentPackage(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!isset($request->id)) {
                return response()->json([
                    'message' => 'User ID Required',
                    'status' => 403
                ])->setStatusCode(403);
            }
            else {
                $check_me = [
                    'username' => $request->id,
                    'status' => 1
                ];
                $check_user = DB::table('user')->where($check_me);
                if ($check_user->count() == 1) {
                    $setting = $this->core();
                    $user = $check_user->first();
                    $ref = $this->generate_ref('AGENT_PACKAGE');
                    $date = $this->system_date();
                    if (!empty($setting->agent_price)) {
                        if ($user->bal > $setting->agent_price) {
                            $debit_user = $user->bal - $setting->agent_price;
                            $credit_user = $debit_user + $setting->agent_price;
                            if ($this->updateData(['bal' => $debit_user], 'user', ['username' => $user->username, 'id' => $user->id])) {
                                if (DB::table('message')->where('transid', $ref)->count() == 0) {
                                    $data = [
                                        'username' => $user->username,
                                        'amount' => $setting->agent_price,
                                        'habukhan_date' => $date,
                                        'transid' => $ref,
                                        'plan_status' => 1,
                                        'newbal' => $debit_user,
                                        'oldbal' => $user->bal,
                                        'message' => 'Successfully Upgraded Your Account To AGENT PACKAGE',
                                        'role' => 'UPGRADE'
                                    ];
                                    if ($this->inserting_data('message', $data)) {
                                        $this->updateData(['type' => 'AGENT'], 'user', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Account Upgraded To AGENT PACKAGE Successfully'
                                        ]);
                                    }
                                    else {
                                        $this->updateData(['bal' => $credit_user], 'user', ['username' => $user->username, 'id' => $user->id]);
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Try Again Later'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    $this->updateData(['bal' => $credit_user], 'user', ['username' => $user->username, 'id' => $user->id]);
                                    return response()->json([
                                        'status' => 403,
                                        'message' => 'Try Again Later'
                                    ])->setStatusCode(403);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'System Unavialable Right Now'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Insufficient Account, Fund Your Wallet And Try Again ~ ₦' . number_format($user->bal, 2)
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'System is unable to Detect Price Right Now'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to verify User',
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function SystemNetwork(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'status' => 'success',
                'network' => DB::table('network')->select('network', 'network_vtu', 'network_share', 'network_sme', 'network_cg', 'network_g', 'plan_id', 'cash', 'data_card', 'recharge_card')->get()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
        }
    }

    public function checkNetworkType(Request $type)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $type->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($type->id)) {
                if (isset($type->token)) {
                    $network = DB::table('network')->where('plan_id', $type->id)->first();
                    $user = DB::table('user')->where(['id' => $this->verifytoken($type->token), 'status' => 1]);
                    if ($user->count() == 1) {
                        $habukhan = $user->first();
                        if ($habukhan->type == 'SMART') {
                            $user_type = strtolower($habukhan->type);
                        }
                        else if ($habukhan->type == 'AGENT') {
                            $user_type = strtolower($habukhan->type);
                        }
                        else if ($habukhan->type == 'AWUF') {
                            $user_type = strtolower($habukhan->type);
                        }
                        else if ($habukhan->type == 'API') {
                            $user_type = strtolower($habukhan->type);
                        }
                        else {
                            $user_type = 'special';
                        }
                        if ($network->network == '9MOBILE') {
                            $real_network = 'mobile';
                        }
                        else {
                            $real_network = $network->network;
                        }
                        $check_for_vtu = strtolower($real_network) . "_vtu_" . $user_type;
                        $check_for_sns = strtolower($real_network) . "_share_" . $user_type;
                        $airtime_discount = DB::table('airtime_discount')->first();


                        return response()->json([
                            'status' => 'success',
                            'network' => $network,
                            'price_vtu' => $airtime_discount->$check_for_vtu,
                            'price_sns' => $airtime_discount->$check_for_sns
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload Your Browser'
                        ])->setStatusCode(403);
                    }
                }
                else {
                    $network = DB::table('network')->where('plan_id', $type->id)->first();
                    return response()->json([
                        'status' => 'success',
                        'network' => $network,
                    ]);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'network plan id need'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }

    public function DeleteUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('user')->where('username', $username);
                            if ($delete_user->count() > 0) {
                                $delete = DB::table('user')->where('username', $username)->delete();
                                DB::table('wallet_funding')->where('username', $username)->delete();
                            }
                            else {
                                $delete = false;
                            }
                        }
                        if ($delete) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Deleted Successfully'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To delete Account'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID  Required'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function singleDelete(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $check_user = DB::table('user')->where('username', $request->username);
                        if ($check_user->count() > 0) {
                            if (DB::table('user')->where('username', $request->username)->delete()) {
                                DB::table('wallet_funding')->where('username', $request->username)->delete();
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Account Deleted Successfully'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable To delete Account'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'User Not Found'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID  Required'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function UserNotif(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $habukhan = $check_user->first();
                    $habukhan_username = $habukhan->username;
                    // user request
                    $user_request = DB::table('notif')->where('username', $habukhan_username);
                    if ($user_request->count() > 0) {
                        foreach ($user_request->orderBy('id', 'desc')->get() as $habukhan) {
                            $select_user = DB::table('user')->where('username', $habukhan->username);
                            if ($select_user->count() > 0) {
                                $users = $select_user->first();
                                if ($users->profile_image !== null) {
                                    $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->profile_image, 'status' => $habukhan->habukhan];
                                }
                                else {
                                    $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->username, 'status' => $habukhan->habukhan];
                                }
                            }
                            else {
                                $profile_image[] = ['username' => $habukhan->username, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $habukhan->username, 'status' => $habukhan->habukhan];
                            }
                        }
                        return response()->json([
                            'status' => 'success',
                            'notif' => $profile_image
                        ]);
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
        }
    }
    public function ClearNotifUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() > 0) {
                    $habukhan = $check_user->first();
                    $habukhan_username = $habukhan->username;
                    // user request
                    DB::table('notif')->where('username', $habukhan_username)->delete();
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
        }
    }

    public function CableName(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'status' => 'success',
                'cable' => DB::table('cable_result_lock')->first()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BillCal(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            if ((isset($request->id)) && (!empty($request->id))) {
                if (is_numeric($request->id)) {
                    $bill_d = DB::table('bill_charge')->first();
                    if ($bill_d->direct == 1) {
                        $charges = $bill_d->bill;
                    }
                    else {
                        $charges = ($request->id / 100) * $bill_d->bill;
                    }
                    return response()->json([
                        'status' => 'suucess',
                        'charges' => $charges
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'invalid amount'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function DiscoList()
    {
        return response()->json([
            'status' => 'success',
            'bill' => DB::table('bill_plan')->where('plan_status', 1)->select('plan_id', 'disco_name')->get()
        ]);
    }
    public function CashNumber()
    {
        return response()->json([
            'numbers' => DB::table('cash_discount')->select('mtn_number', 'glo_number', 'mobile_number', 'airtel_number')->first()
        ]);
    }
    public function AirtimeCash(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->amount)) {
                if (!empty($request->network)) {

                    if ($request->network == '9MOBILE') {
                        $network_name = 'mobile';
                    }
                    else {
                        $network_name = strtolower($request->network);
                    }
                    $system_admin = DB::table('cash_discount')->first();
                    $credit = ($request->amount / 100) * $system_admin->$network_name;

                    return response()->json([
                        'amount' => $credit,
                        'status' => 'success'
                    ]);
                }
                else {
                    return response()->json([
                        'message' => 'Network Required'
                    ])->setStatusCode(403);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Amount Required'
                ])->setStatusCode(403);
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BulksmsCal(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'amount' => $this->core()->bulk_sms
            ]);
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function ResultPrice(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            return response()->json([
                'price' => DB::table('result_charge')->first()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
        }
    }

    public function getAppInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        if (!$origin || in_array($origin, $explode_url)) {
            $general = $this->general();
            $faqs = DB::table('faqs')->where('status', 1)->get();

            return response()->json([
                'status' => 'success',
                'contact' => [
                    'phone' => $general->app_phone,
                    'email' => $general->app_email,
                    'whatsapp' => $general->app_whatsapp,
                    'address' => $general->app_address,
                    'facebook' => $general->facebook,
                    'tiktok' => $general->tiktok,
                    'instagram' => $general->instagram,

                    // Support Identity
                    'support_ai_name' => 'OyitiPay',
                    'support_call_name' => 'OyitiPay',
                    'support_phone' => $general->app_phone,
                    'support_whatsapp' => $general->app_whatsapp,
                ],
                'faqs' => $faqs
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function emailReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'transid' => 'required',
            'pdf_base64' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $userId = $this->verifyapptoken($request->user_id) ?? $this->verifytoken($request->user_id);
        $user = DB::table('user')->where('id', $userId)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found or session expired'], 401);
        }

        try {
            $pdfData = base64_decode($request->pdf_base64);
            $general = $this->general();

            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => 'Your Purchase Receipt - ' . $request->transid,
                'sender_mail' => $general->app_email,
                'app_name' => config('app.name'),
                'transid' => $request->transid,
                'date' => now()->toDayDateTimeString(),
            ];

            $attachment = [
                'data' => $pdfData,
                'name' => 'Receipt_' . $request->transid . '.pdf',
                'mime' => 'application/pdf'
            ];

            $sent = MailController::send_mail($email_data, 'email.receipt', $attachment);

            if ($sent) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Receipt has been sent to your email: ' . $user->email
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'Failed to send email. Please try again.'], 500);

        }
        catch (\Exception $e) {
            \Log::error('Email Receipt Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An internal error occurred.'], 500);
        }
    }
}