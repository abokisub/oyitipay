<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{

    public function userRequest(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {

                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // user request
                    $user_request = DB::table('request')->select('username', 'message', 'date', 'transid', 'status', 'title', 'transid', 'id');
                    if ($user_request->count() > 0) {
                        foreach ($user_request->orderBy('id', 'desc')->get() as $habukhan) {
                            $select_user = DB::table('user')->where('username', $habukhan->username);
                            if ($select_user->count() > 0) {
                                $users = $select_user->first();
                                if ($users->profile_image !== null) {
                                    $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->profile_image, 'status' => $habukhan->status];
                                }
                                else {
                                    $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $users->username, 'status' => $habukhan->status];
                                }
                            }
                            else {
                                $profile_image[] = ['username' => $habukhan->username, 'transid' => $habukhan->transid, 'title' => $habukhan->title, 'id' => $habukhan->id, 'message' => $habukhan->message, 'date' => $habukhan->date, 'profile_image' => $habukhan->username, 'status' => $habukhan->status];
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
    public function ClearRequest(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    DB::table('request')->delete();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Done'
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function UserSystem(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {

                    $users_info = [
                        'wallet_balance' => DB::table('user')->sum('bal'),
                        'ref_balance' => DB::table('user')->sum('refbal'),
                        'all_user' => DB::table('user')->count(),
                        'smart_total' => DB::table('user')->where('type', 'SMART')->count(),
                        'awuf_total' => DB::table('user')->where('type', 'AWUF')->count(),
                        'special_total' => DB::table('user')->where('type', 'SPECIAL')->count(),
                        'api_total' => DB::table('user')->where('type', 'API')->count(),
                        'agent_total' => DB::table('user')->where('type', 'AGENT')->count(),
                        'customer_total' => DB::table('user')->where('type', 'CUSTOMER')->count(),
                        'admin_total' => DB::table('user')->where('type', 'ADMIN')->count(),
                        'active_user' => DB::table('user')->where('status', 1)->count(),
                        'deactivate_user' => DB::table('user')->where('status', 3)->count(),
                        'banned_user' => DB::table('user')->where('status', 2)->count(),
                        'unverified_user' => DB::table('user')->where('status', 0)->count(),
                        'mtn_cg_bal' => DB::table('wallet_funding')->sum('mtn_cg_bal'),
                        'mtn_g_bal' => DB::table('wallet_funding')->sum('mtn_g_bal'),
                        'mtn_sme_bal' => DB::table('wallet_funding')->sum('mtn_sme_bal'),
                        'airtel_cg_bal' => DB::table('wallet_funding')->sum('airtel_cg_bal'),
                        'airtel_g_bal' => DB::table('wallet_funding')->sum('airtel_g_bal'),
                        'airtel_sme_bal' => DB::table('wallet_funding')->sum('airtel_sme_bal'),
                        'glo_cg_bal' => DB::table('wallet_funding')->sum('glo_cg_bal'),
                        'glo_g_bal' => DB::table('wallet_funding')->sum('glo_g_bal'),
                        'glo_sme_bal' => DB::table('wallet_funding')->sum('glo_sme_bal'),
                        'mobile_cg_bal' => DB::table('wallet_funding')->sum('mobile_cg_bal'),
                        'mobile_g_bal' => DB::table('wallet_funding')->sum('mobile_g_bal'),
                        'mobile_sme_bal' => DB::table('wallet_funding')->sum('mobile_sme_bal'),
                        'total_process' => DB::table('message')->where(['plan_status' => 0])->count(),
                        'total_data_proccess' => DB::table('data')->where(['plan_status' => 0])->count(),
                        // Charity Stats
                        'charity_escrow' => DB::table('charities')->sum('pending_balance'),
                        'charity_available' => DB::table('charities')->sum('available_balance'),
                        'total_donations' => DB::table('donations')->sum('amount'),
                        'today_donations' => $today_donations = DB::table('donations')->whereDate('created_at', Carbon::today())->sum('amount'),
                        'total_campaigns' => DB::table('campaigns')->count(),
                        'total_organizations' => DB::table('charities')->count(),

                        'today_data_success' => $today_data = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->count(),
                        'today_airtime_success' => $today_airtime = DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->count(),
                        'today_sales' => $today_sales = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->sum('amount') +
                        DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::today())->sum('discount') +
                        $today_donations,

                        'yesterday_sales' => $yesterday_sales = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->sum('amount') +
                        DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->sum('discount') +
                        DB::table('donations')->whereDate('created_at', Carbon::yesterday())->sum('amount'),

                        'yesterday_trans' => $yesterday_trans = DB::table('data')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->count() +
                        DB::table('airtime')->where(['plan_status' => 1])->whereDate('plan_date', Carbon::yesterday())->count() +
                        DB::table('donations')->whereDate('created_at', Carbon::yesterday())->count(),

                        'sales_percent' => $yesterday_sales > 0 ? round((($today_sales - $yesterday_sales) / $yesterday_sales) * 100, 1) : ($today_sales > 0 ? 100 : 0),
                        'trans_percent' => $yesterday_trans > 0 ? round(((($today_data + $today_airtime + DB::table('donations')->whereDate('created_at', Carbon::today())->count()) - $yesterday_trans) / $yesterday_trans) * 100, 1) : (($today_data + $today_airtime + DB::table('donations')->whereDate('created_at', Carbon::today())->count()) > 0 ? 100 : 0),

                        'total_pending' => DB::table('data')->where('plan_status', 0)->count() +
                        DB::table('airtime')->where('plan_status', 0)->count() +
                        DB::table('cable')->where('plan_status', 0)->count() +
                        DB::table('bill')->where('plan_status', 0)->count() +
                        DB::table('campaigns')->where('payout_status', 'pending')->where('status', 'closed')->count(),
                    ];

                    return response()->json([
                        'status' => 'success',
                        'user' => $users_info,
                        'payment' => DB::table('habukhan_key')->first(),
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function editUserDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    if (!empty($request->username)) {
                        $verify_user = DB::table('user')->where('id', $request->username);
                        if ($verify_user->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'user' => $verify_user->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'User ID Not Found'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'User ID Required'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CreateNewUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required|max:199|min:8',
                        'email' => 'required|unique:user,email|max:255|email',
                        'phone' => 'required|numeric|unique:user,phone|digits:11',
                        'password' => 'required|min:8',
                        'username' => 'required|unique:user,username|max:12|string|alpha_num',
                        'status' => 'required',
                        'type' => 'required'
                    ], [
                        'name.required' => 'Full Name is Required',
                        'email.required' => 'E-mail is Required',
                        'phone.required' => 'Phone Number Required',
                        'password.required' => 'Password Required',
                        'username.required' => 'Username Required',
                        'username.unique' => 'Username already Taken',
                        'phone.unique' => 'Phone Number already Taken',
                        'username.max' => 'Username Maximum Length is 12 ' . $request->username,
                        'email.unique' => 'Email Alreay Taken',
                        'password.min' => 'Password Not Strong Enough',
                        'name.min' => 'Invalid Full Name',
                        'name.max' => 'Invalid Full Name',
                        'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,
                        'status.required' => 'Account Status Required',
                        'type.required' => 'Account Role Required'
                    ]);
                    //declaring user status
                    if ($request->status == 'Active' || $request->status == 1) {
                        $status = 1;
                    }
                    else if ($request->status == 'Deactivate' || $request->status == 3) {
                        $status = 3;
                    }
                    else if ($request->status == 'Banned' || $request->status == 2) {
                        $status = 2;
                    }
                    else if ($request->status == 'Unverified' || $request->status == 0) {
                        $status = 0;
                    }
                    else {
                        $status = 0;
                    }

                    //system kyc
                    if ($request->kyc == 'true') {
                        $kyc = 1;
                    }
                    else {
                        $kyc = 0;
                    }
                    //checking referral username
                    if ($request->ref != null) {
                        $check_ref = DB::table('user')
                            ->where('username', '=', $request->ref)
                            ->count();
                    }
                    //profile_image
                    if ($request->hasFile('profile_image')) {
                        $validator = validator::make($request->all(), [
                            'profile_image' => 'required|image|max:2047|mimes:jpg,png,jpeg',
                        ]);
                        if ($validator->fails()) {
                            $path = null;
                            return response()->json([
                                'message' => $validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else {
                            $profile_image = $request->file('profile_image');
                            $profile_image_name = $request->username . '_' . $profile_image->getClientOriginalName();
                            $save_here = 'profile_image';
                            $path = $request->file('profile_image')->storeAs($save_here, $profile_image_name);
                        }
                    }
                    else {
                        $path = null;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if (substr($request->phone, 0, 1) != '0') {
                        return response()->json([
                            'message' => 'Invalid Phone Number',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if ($request->ref != null && $check_ref == 0) {
                        return response()->json([
                            'message' => 'Invalid Referral Username You can Leave the referral Box Empty',
                            'status' => '403'
                        ])->setStatusCode(403);
                    }
                    elseif ($request->pin != null && !is_numeric($request->pin)) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Transaction Pin Must be Numeric'
                        ])->setStatusCode(403);
                    }
                    else if ($request->pin != null && strlen($request->pin) != 4) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Transaction Pin Must be 4 Digit'
                        ])->setStatusCode(403);
                    }
                    else {
                        // checking
                        $user = new User();
                        $user->name = $request->name;
                        $user->username = $request->username;
                        $user->email = $request->email;
                        $user->phone = $request->phone;
                        $user->password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                        // $user->password = Hash::make($request->password);
                        $user->apikey = bin2hex(openssl_random_pseudo_bytes(30));
                        $user->bal = '0.00';
                        $user->refbal = '0.00';
                        $user->ref = $request->ref;
                        $user->type = $request->type;
                        $user->date = Carbon::now("Africa/Lagos");
                        $user->kyc = $kyc;
                        $user->status = $status;
                        $user->user_limit = $this->habukhan_key()->default_limit;
                        $user->pin = $request->pin;
                        $user->webhook = $request->webhook;
                        $user->about = $request->about;
                        $user->address = $request->address;
                        $user->profile_image = url('') . '/' . $path;
                        $user->save();
                        if ($user != null) {
                            $general = $this->general();
                            if ($status == 0 && $request->isVerified == false) {
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
                            }
                            else {
                                $email_data = [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'username' => $user->username,
                                    'title' => 'WELCOME EMAIL',
                                    'sender_mail' => $general->app_email,
                                    'system_email' => $general->app_email,
                                    'app_name' => $general->app_name
                                ];
                                MailController::send_mail($email_data, 'email.welcome');
                            }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Created'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to Register User'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function ChangeApiKey(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    if (DB::table('user')->where('username', $request->username)->count() > 0) {
                        if ($this->updateData(['apikey' => bin2hex(openssl_random_pseudo_bytes(30))], 'user', ['username' => $request->username])) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'ApiKey Upgraded'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'An Error Occured'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid User ID'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function EditUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    //validate all here
                    if (DB::table('user')->where(['id' => $request->user_id])->count() == 1) {
                        $main_validator = validator::make($request->all(), [
                            'name' => 'required',
                            'email' => "required|unique:user,email,$request->user_id",
                            'phone' => "required|numeric|unique:user,phone,$request->user_id|digits:11",
                            'status' => 'required',
                            'type' => 'required',
                            'user_limit' => 'required|numeric|digits_between:2,6',
                        ], [
                            'name.required' => 'Full Name is Required',
                            'email.required' => 'E-mail is Required',
                            'phone.required' => 'Phone Number Required',
                            'username.required' => 'Username Required',
                            'username.unique' => 'Username already Taken',
                            'phone.unique' => 'Phone Number already Taken',
                            'username.max' => 'Username Maximum Length is 12 ' . $request->username,
                            'email.unique' => 'Email Alreay Taken',
                            'password.min' => 'Password Not Strong Enough',
                            'name.min' => 'Invalid Full Name',
                            'name.max' => 'Invalid Full Name',
                            'phone.numeric' => 'Phone Number Must be Numeric ' . $request->phone,
                            'status.required' => 'Account Status Required',
                            'type.required' => 'Account Role Required'
                        ]);
                        //declaring user status
                        if ($request->status == 'Active' || $request->status == 1) {
                            $status = 1;
                        }
                        else if ($request->status == 'Deactivate' || $request->status == 3) {
                            $status = 3;
                        }
                        else if ($request->status == 'Banned' || $request->status == 2) {
                            $status = 2;
                        }
                        else if ($request->status == 'Unverified' || $request->status == 0) {
                            $status = 0;
                        }
                        else {
                            $status = 0;
                        }

                        //system kyc
                        if ($request->kyc == 'true') {
                            $kyc = 1;
                        }
                        else {
                            $kyc = 0;
                        }
                        //checking referral username
                        if ($request->ref != null) {
                            $check_ref = DB::table('user')
                                ->where('username', '=', $request->ref)
                                ->count();
                        }
                        //profile_image
                        if ($request->hasFile('profile_image')) {
                            $validator = validator::make($request->all(), [
                                'profile_image' => 'required|image|max:2047|mimes:jpg,png,jpeg',
                            ]);
                            if ($validator->fails()) {
                                $path = null;
                                return response()->json([
                                    'message' => $validator->errors()->first(),
                                    'status' => 403
                                ])->setStatusCode(403);
                            }
                            else {
                                $profile_image = $request->file('profile_image');
                                $profile_image_name = $request->username . '_' . $profile_image->getClientOriginalName();
                                $save_here = 'profile_image';
                                $path = url('') . '/' . $request->file('profile_image')->storeAs($save_here, $profile_image_name);
                            }
                        }
                        else {
                            $path = $request->profile_image;
                        }
                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else if (substr($request->phone, 0, 1) != '0') {
                            return response()->json([
                                'message' => 'Invalid Phone Number',
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else if ($request->ref != null && $check_ref == 0) {
                            return response()->json([
                                'message' => 'Invalid Referral Username You can Leave the referral Box Empty',
                                'status' => '403'
                            ])->setStatusCode(403);
                        }
                        elseif ($request->pin != null && !is_numeric($request->pin)) {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Transaction Pin Must be Numeric'
                            ])->setStatusCode(403);
                        }
                        else if ($request->pin != null && strlen($request->pin) != 4) {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Transaction Pin Must be 4 Digit'
                            ])->setStatusCode(403);
                        }
                        else {
                            // updateing
                            $user = User::find($request->user_id);
                            $user->name = $request->name;
                            $user->email = $request->email;
                            $user->phone = $request->phone;
                            $user->ref = $request->ref;
                            $user->type = $request->type;
                            $user->kyc = $kyc;
                            $user->status = $status;
                            $user->user_limit = $request->user_limit;
                            $user->reason = $request->reason;
                            $user->pin = $request->pin;
                            $user->webhook = $request->webhook;
                            $user->about = $request->about;
                            $user->address = $request->address;
                            $user->profile_image = $path;
                            // Removed non-existent columns: sterlen, wema, kolomoni_mfb, fed, otp
                            $user->Update();
                            if ($user != null) {
                                $general = $this->general();
                                if ($status == 0 && $request->isVerified == false) {
                                    $otp = random_int(100000, 999999);
                                    $data = [
                                        'otp' => $otp
                                    ];
                                    $tableid = [
                                        'username' => $request->username
                                    ];
                                    $this->updateData($data, 'user', $tableid);
                                    $email_data = [
                                        'name' => $request->name,
                                        'email' => $request->email,
                                        'username' => $request->username,
                                        'title' => 'Account Verification',
                                        'sender_mail' => $general->app_email,
                                        'app_name' => config('app.name'),
                                        'otp' => $otp
                                    ];
                                    MailController::send_mail($email_data, 'email.verify');
                                }
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Success'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable to Update User'
                                ])->setStatusCode(403);
                            }
                        }
                    }
                    else {
                        return response()->json([
                            'staus' => 403,
                            'message' => 'An Error Occured'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function FilterUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                if ($check_user->count() > 0) {
                    $users = DB::table('user')->where('username', 'LIKE', "%$request->username%")->orWhere('email', 'LIKE', "%$request->username%")->orWhere('phone', 'LIKE', "%$request->username%")->orWhere('name', 'LIKE', "%$request->username%")->limit(10)
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'user' => $users
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CreditUserHabukhan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                });
                $admin = $check_user->first();
                $general = $this->general();
                $all_admin = DB::table('user')->where(['status' => 1])->where(function ($query) {
                    $query->where('type', 'ADMIN')->orwhere('type', 'CUSTOMER');
                })->get();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'wallet' => 'required|string',
                        'amount' => 'required|numeric|integer|not_in:0|gt:0',
                        'credit' => 'required|string',
                        'reason' => 'required|string'
                    ], [
                        'credit.required' => 'Credit/Debit Required',
                        'wallet.required' => 'User Wallet Required'
                    ]);
                    //get which user
                    $user = DB::table('user')->where('username', $request->user_username);
                    $user_details = $user->first();
                    // wallet statement
                    if ($request->wallet == 'wallet') {
                        $wallet = 'User Wallet';
                    }
                    else if ($request->wallet == 'mtn_cg_bal') {
                        $wallet = 'MTN CG WALLET';
                    }
                    else if ($request->wallet == 'mtn_g_bal') {
                        $wallet = 'MTN GIFTING WALLET';
                    }
                    else if ($request->wallet == 'mtn_sme_bal') {
                        $wallet = 'MTN SME WALLET';
                    }
                    else if ($request->wallet == 'airtel_cg_bal') {
                        $wallet = 'AIRTEL CG WALLET';
                    }
                    else if ($request->wallet == 'airtel_g_bal') {
                        $wallet = 'AIRTEL GIFTING WALLET';
                    }
                    else if ($request->wallet == 'airtel_sme_bal') {
                        $wallet = 'AIRTEL SME WALLET';
                    }
                    else if ($request->wallet == 'glo_cg_bal') {
                        $wallet = 'GLO CG WALLET';
                    }
                    else if ($request->wallet == 'glo_g_bal') {
                        $wallet = 'GLO GIFTING WALLET';
                    }
                    else if ($request->wallet == 'glo_sme_bal') {
                        $wallet = 'GLO SME WALLET';
                    }
                    else if ($request->wallet == 'mobile_cg_bal') {
                        $wallet = '9MOBILE CG WALLET';
                    }
                    else if ($request->wallet == 'mobile_g_bal') {
                        $wallet = '9MOBILE GIFTING WALLET';
                    }
                    else if ($request->wallet == 'mobile_sme_bal') {
                        $wallet = '9MOBILE SME WALLET';
                    }
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if (empty($wallet)) {
                        return response()->json([
                            'message' => 'Account Wallet Not Found',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if ($request->credit == 'credit') {
                            $all_amount_credited = DB::table('deposit')->where(['credit_by' => $admin->username, 'status' => 1])->where('date', '>=', Carbon::now())->sum('amount');
                            if ($admin->type == 'CUSTOMER' && $request->amount > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Maximum Amount to Credit Users Daily is ₦' . number_format($this->core()->customer_amount, 2)
                                ])->setStatusCode(403);
                            }
                            else if ($admin->type == 'CUSTOMER' && $all_amount_credited > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Credit User Daily Amount Exhausted'
                                ])->setStatusCode(403);
                            }
                            else if ($admin->type == 'CUSTOMER' && $all_amount_credited + $request->amount > $this->core()->customer_amount) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Daliy Amount Remaining To Credit A User is ₦' . number_format($this->core()->customer_amount - $all_amount_credited, 2)
                                ])->setStatusCode(403);
                            }
                            else {
                                $deposit_ref = $this->generate_ref('Credit');
                                // crediting users here
                                if ($request->wallet == 'wallet') {
                                    //credit user wallet
                                    // now update user
                                    $update_data = [
                                        'bal' => $user_details->bal + $request->amount
                                    ];
                                    if ($this->updateData($update_data, 'user', ['id' => $user_details->id])) {
                                        // insert into message
                                        $message_data = [
                                            'username' => $user_details->username,
                                            'amount' => $request->amount,
                                            'message' => $request->reason,
                                            'oldbal' => $user_details->bal,
                                            'newbal' => $user_details->bal + $request->amount,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $deposit_ref,
                                            'role' => 'credit'
                                        ];
                                        $this->inserting_data('message', $message_data);
                                        // inserting notif
                                        // inserting notif
                                        (new \App\Services\NotificationService())->sendWalletCreditNotification($user_details, $request->amount, 'Admin Funding', $deposit_ref);
                                        // inserting into deposit table
                                        $deposit_data = [
                                            'username' => $user_details->username,
                                            'amount' => $request->amount,
                                            'oldbal' => $user_details->bal,
                                            'newbal' => $user_details->bal + $request->amount,
                                            'wallet_type' => $wallet,
                                            'type' => 'Admin Funding',
                                            'credit_by' => $admin->username,
                                            'date' => $this->system_date(),
                                            'status' => 1,
                                            'transid' => $deposit_ref,
                                            'charges' => 0.0
                                        ];
                                        $this->inserting_data('deposit', $deposit_data);

                                        // Handle referral for manual credit
                                        if ($this->core()->referral == 1 && $user_details->ref) {
                                            if (DB::table('deposit')->where(['username' => $user_details->username, 'status' => 1])->count() == 1) {
                                                if (DB::table('user')->where(['username' => $user_details->ref, 'status' => 1])->exists()) {
                                                    $user_ref = DB::table('user')->where(['username' => $user_details->ref, 'status' => 1])->first();
                                                    $credit_ref = ($request->amount / 100) * $this->core()->referral_price;
                                                    DB::table('user')->where(['username' => $user_details->ref, 'status' => 1])->update(['refbal' => $user_ref->refbal + $credit_ref]);

                                                    DB::table('message')->insert([
                                                        'username' => $user_ref->username,
                                                        'amount' => $credit_ref,
                                                        'message' => 'Referral Earning From ' . ucfirst($user_details->username),
                                                        'oldbal' => $user_ref->refbal,
                                                        'newbal' => $user_ref->refbal + $credit_ref,
                                                        'habukhan_date' => $this->system_date(),
                                                        'plan_status' => 1,
                                                        'transid' => $deposit_ref . '-REF',
                                                        'role' => 'referral'
                                                    ]);
                                                }
                                            }
                                        }

                                        if ($request->isnotif == true) {
                                            //sending mail over here
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $user_details->email,
                                                'username' => $user_details->username,
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->bal, 2),
                                                'newbal' => number_format($user_details->bal + $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref
                                            ];
                                            MailController::send_mail($email_data, 'email.deposit');
                                        }
                                        foreach ($all_admin as $habukhan) {
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $habukhan->email,
                                                'username' => strtoupper($user_details->username),
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->bal, 2),
                                                'newbal' => number_format($user_details->bal + $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref,
                                                'credited_by' => strtoupper($admin->username)
                                            ];
                                            MailController::send_mail($email_data, 'email.admin');
                                        }
                                        return response()->json([
                                            'status' => 'success',
                                            'account_type' => $wallet,
                                            'message' => 'Account Credited SuccessFully'
                                        ]);
                                    }
                                    else {
                                        return response()->json([
                                            'message' => 'Unable to Credit User',
                                            'status' => 403
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    // funding the wallet funding (Stock Funding)
                                    $stock_user_wallet = DB::table('wallet_funding')->where('username', $request->user_username);
                                    if ($stock_user_wallet->count() == 1) {
                                        $user_stock_details = $stock_user_wallet->first();
                                        $ad = $request->wallet;
                                        $update_data = [
                                            $request->wallet => $user_stock_details->$ad + $request->amount
                                        ];
                                        if ($this->updateData($update_data, 'wallet_funding', ['id' => $user_stock_details->id])) {
                                            // insert into message
                                            $message_data = [
                                                'username' => $user_details->username,
                                                'amount' => $request->amount,
                                                'message' => $request->reason,
                                                'oldbal' => $user_stock_details->$ad,
                                                'newbal' => $user_stock_details->$ad + $request->amount,
                                                'habukhan_date' => $this->system_date(),
                                                'plan_status' => 1,
                                                'transid' => $deposit_ref,
                                                'role' => 'credit'
                                            ];
                                            $this->inserting_data('message', $message_data);
                                            // inserting notif
                                            // inserting notif
                                            (new \App\Services\NotificationService())->sendWalletCreditNotification($user_details, $request->amount, 'Admin Funding', $deposit_ref);
                                            // inserting into deposit table
                                            $deposit_data = [
                                                'username' => $user_details->username,
                                                'amount' => $request->amount,
                                                'oldbal' => $user_stock_details->$ad,
                                                'newbal' => $user_stock_details->$ad + $request->amount,
                                                'wallet_type' => $wallet,
                                                'type' => 'Admin Funding',
                                                'credit_by' => $admin->username,
                                                'date' => $this->system_date(),
                                                'status' => 1,
                                                'transid' => $deposit_ref,
                                                'charges' => 0.00
                                            ];
                                            $this->inserting_data('deposit', $deposit_data);
                                            if ($request->isnotif == true) {
                                                //sending mail over here
                                                $email_data = [
                                                    'name' => $user_details->name,
                                                    'email' => $user_details->email,
                                                    'username' => $user_details->username,
                                                    'title' => 'Account Funding',
                                                    'sender_mail' => $general->app_email,
                                                    'app_name' => config('app.name'),
                                                    'wallet' => $wallet,
                                                    'amount' => number_format($request->amount, 2),
                                                    'oldbal' => number_format($user_stock_details->$ad, 2),
                                                    'newbal' => number_format($user_stock_details->$ad + $request->amount, 2),
                                                    'deposit_type' => strtoupper($request->credit),
                                                    'transid' => $deposit_ref
                                                ];
                                                MailController::send_mail($email_data, 'email.deposit');
                                            }
                                            foreach ($all_admin as $habukhan) {
                                                $email_data = [
                                                    'name' => $user_details->name,
                                                    'email' => $habukhan->email,
                                                    'username' => strtoupper($user_details->username),
                                                    'title' => 'Account Funding',
                                                    'sender_mail' => $general->app_email,
                                                    'app_name' => config('app.name'),
                                                    'wallet' => $wallet,
                                                    'amount' => number_format($request->amount, 2),
                                                    'oldbal' => number_format($user_details->bal, 2),
                                                    'newbal' => number_format($user_details->bal + $request->amount, 2),
                                                    'deposit_type' => strtoupper($request->credit),
                                                    'transid' => $deposit_ref,
                                                    'credited_by' => strtoupper($admin->username)
                                                ];
                                                MailController::send_mail($email_data, 'email.admin');
                                            }
                                            return response()->json([
                                                'status' => 'success',
                                                'account_type' => $wallet,
                                                'message' => 'Account Credited SuccessFully'
                                            ]);
                                        }
                                        else {
                                            return response()->json([
                                                'status' => 403,
                                                'message' => 'Unable to Fund User Stock Wallet'
                                            ])->setStatusCode(403);
                                        }
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => strtoupper($user_details->username) . ' has not login and is wallet funnding account has not been created'
                                        ])->setStatusCode(403);
                                    }
                                }
                            }
                        }
                        else if ($request->credit == 'debit') {
                            $deposit_ref = $this->generate_ref('Debit');
                            // debiting user over here
                            if ($request->wallet == 'wallet') {
                                // debiting ain wallet
                                $update_data = [
                                    'bal' => $user_details->bal - $request->amount
                                ];
                                if ($this->updateData($update_data, 'user', ['id' => $user_details->id])) {
                                    // insert into message
                                    $message_data = [
                                        'username' => $user_details->username,
                                        'amount' => $request->amount,
                                        'message' => $request->reason,
                                        'oldbal' => $user_details->bal,
                                        'newbal' => $user_details->bal - $request->amount,
                                        'habukhan_date' => $this->system_date(),
                                        'plan_status' => 1,
                                        'transid' => $deposit_ref,
                                        'role' => 'debit'
                                    ];
                                    $this->inserting_data('message', $message_data);
                                    // inserting notif
                                    // inserting notif
                                    (new \App\Services\NotificationService())->sendWalletDebitNotification($user_details, $request->amount, 'Admin Debit', $deposit_ref);
                                    if ($request->isnotif == true) {
                                        //sending mail over here
                                        $email_data = [
                                            'name' => $user_details->name,
                                            'email' => $user_details->email,
                                            'username' => $user_details->username,
                                            'title' => 'Account Debited',
                                            'sender_mail' => $general->app_email,
                                            'app_name' => config('app.name'),
                                            'wallet' => $wallet,
                                            'amount' => number_format($request->amount, 2),
                                            'oldbal' => number_format($user_details->bal, 2),
                                            'newbal' => number_format($user_details->bal - $request->amount, 2),
                                            'deposit_type' => strtoupper($request->credit),
                                            'transid' => $deposit_ref
                                        ];
                                        MailController::send_mail($email_data, 'email.deposit');
                                    }
                                    foreach ($all_admin as $habukhan) {
                                        $email_data = [
                                            'name' => $user_details->name,
                                            'email' => $habukhan->email,
                                            'username' => strtoupper($user_details->username),
                                            'title' => 'Account Funding',
                                            'sender_mail' => $general->app_email,
                                            'app_name' => config('app.name'),
                                            'wallet' => $wallet,
                                            'amount' => number_format($request->amount, 2),
                                            'oldbal' => number_format($user_details->bal, 2),
                                            'newbal' => number_format($user_details->bal - $request->amount, 2),
                                            'deposit_type' => strtoupper($request->credit),
                                            'transid' => $deposit_ref,
                                            'credited_by' => strtoupper($admin->username)
                                        ];
                                        MailController::send_mail($email_data, 'email.admin');
                                    }
                                    return response()->json([
                                        'status' => 'success',
                                        'account_type' => $wallet,
                                        'message' => 'Account Debited SuccessFully'
                                    ]);
                                }
                                else {
                                    return response()->json([
                                        'message' => 'Unable to Debit User',
                                        'status' => 403
                                    ])->setStatusCode(403);
                                }
                            }
                            else {
                                // debiting stock wallet
                                $stock_user_wallet = DB::table('wallet_funding')->where('username', $request->user_username);
                                if ($stock_user_wallet->count() == 1) {
                                    $user_stock_details = $stock_user_wallet->first();
                                    $ad = $request->wallet;
                                    $update_data = [
                                        $request->wallet => $user_stock_details->$ad - $request->amount
                                    ];
                                    if ($this->updateData($update_data, 'wallet_funding', ['id' => $user_stock_details->id])) {
                                        // insert into message
                                        $message_data = [
                                            'username' => $user_details->username,
                                            'amount' => $request->amount,
                                            'message' => $request->reason,
                                            'oldbal' => $user_stock_details->$ad,
                                            'newbal' => $user_stock_details->$ad - $request->amount,
                                            'habukhan_date' => $this->system_date(),
                                            'plan_status' => 1,
                                            'transid' => $deposit_ref,
                                            'role' => 'debit'
                                        ];
                                        $this->inserting_data('message', $message_data);
                                        // inserting notif
                                        // inserting notif
                                        (new \App\Services\NotificationService())->sendWalletDebitNotification($user_details, $request->amount, 'Admin Debit', $deposit_ref);
                                        if ($request->isnotif == true) {
                                            //sending mail over here
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $user_details->email,
                                                'username' => $user_details->username,
                                                'title' => 'Account Debited',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_stock_details->$ad, 2),
                                                'newbal' => number_format($user_stock_details->$ad - $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref
                                            ];
                                            MailController::send_mail($email_data, 'email.deposit');
                                        }
                                        foreach ($all_admin as $habukhan) {
                                            $email_data = [
                                                'name' => $user_details->name,
                                                'email' => $habukhan->email,
                                                'username' => strtoupper($user_details->username),
                                                'title' => 'Account Funding',
                                                'sender_mail' => $general->app_email,
                                                'app_name' => config('app.name'),
                                                'wallet' => $wallet,
                                                'amount' => number_format($request->amount, 2),
                                                'oldbal' => number_format($user_details->bal, 2),
                                                'newbal' => number_format($user_details->bal - $request->amount, 2),
                                                'deposit_type' => strtoupper($request->credit),
                                                'transid' => $deposit_ref,
                                                'credited_by' => strtoupper($admin->username)
                                            ];
                                            MailController::send_mail($email_data, 'email.admin');
                                        }
                                        return response()->json([
                                            'status' => 'success',
                                            'account_type' => $wallet,
                                            'message' => 'Account Debited SuccessFully'
                                        ]);
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Unable to Debit User Stock Wallet'
                                        ])->setStatusCode(403);
                                    }
                                }
                                else {
                                    return response()->json([
                                        'status' => 403,
                                        'message' => strtoupper($user_details->username) . ' has not login and is wallet funnding account has not been created'
                                    ])->setStatusCode(403);
                                }
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Account Debit/Credit Unknown'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function UpgradeUserAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                $general = $this->general();
                $user = DB::table('user')->where('username', $request->user_username);
                $details = $user->first();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'role' => 'required|string',
                    ], [
                        'role.required' => 'Account Role Required',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (
                        $this->updateData([
                        'type' => $request->role
                        ], 'user', ['id' => $details->id])
                        ) {
                            $dis = $this->generate_ref('Upgrade/Downgrade');
                            $message_data = [
                                'username' => $details->username,
                                'amount' => 0.00,
                                'message' => 'Your Acount Has Been Upgrade to ' . $request->role . ' Package',
                                'oldbal' => $details->bal,
                                'newbal' => $details->bal,
                                'habukhan_date' => $this->system_date(),
                                'plan_status' => 1,
                                'transid' => $dis,
                                'role' => 'upgrade'
                            ];
                            $this->inserting_data('message', $message_data);
                            if ($request->isnotif == true) {
                                //sending mail over here
                                $email_data = [
                                    'name' => $details->name,
                                    'email' => $details->email,
                                    'username' => $details->username,
                                    'title' => 'Account Upgrade/Downgrade',
                                    'sender_mail' => $general->app_email,
                                    'app_name' => config('app.name'),
                                    'amount' => 0.00,
                                    'oldbal' => number_format($details->bal, 2),
                                    'newbal' => number_format($details->bal, 2),
                                    'deposit_type' => strtoupper($request->credit),
                                    'transid' => $dis,
                                    'role' => $request->role
                                ];
                                MailController::send_mail($email_data, 'email.upgrade');
                            }
                            // inserting notif
                            $notif_data = [
                                'username' => $details->username,
                                'message' => 'Your Acount Has Been Upgrade to ' . $request->role . ' Package',
                                'date' => $this->system_date(),
                                'habukhan' => 0
                            ];
                            $this->inserting_data('notif', $notif_data);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Acount Upgraded'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to upgrade user'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function ResetUserPassword(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                $general = $this->general();
                $user = DB::table('user')->where('username', $request->user_username);
                $details = $user->first();
                if ($check_user->count() > 0) {
                    $validator = validator::make($request->all(), [
                        'user_username' => 'required|string',
                        'password' => 'required|string|min:8',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'message' => $validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if ($user->count() != 1) {
                        return response()->json([
                            'message' => 'Unable to Get the Correspond User Username',
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (
                        $this->updateData([
                        'password' => password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16)),
                        ], 'user', ['id' => $details->id])
                        ) {
                            if ($request->isnotif == true) {
                                //sending mail over here
                                $email_data = [
                                    'name' => $details->name,
                                    'email' => $details->email,
                                    'title' => 'Password Reset',
                                    'sender_mail' => $general->app_email,
                                    'app_name' => config('app.name'),
                                    'password' => $request->password,
                                    'username' => $details->username
                                ];
                                MailController::send_mail($email_data, 'email.admin_reset');
                            }
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Password Reseted'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable to Reset User Password'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function Automated(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('user')->where('username', $username);
                            $user_id = $delete_user->first();
                            if ($user_id) {
                                $id = $user_id->id;
                                $data = [
                                    'autofund' => null,
                                    // Removed non-existent columns: wema, kolomoni_mfb, sterlen, fed
                                ];
                                $updated = $this->updateData($data, 'user', ['id' => $id]);
                                if ($updated || $updated === 0) {
                                    $successCount++;
                                }
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Details Deleted Successfully'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To delete Account or No Changes Made'
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
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BankDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->username)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->username); $i++) {
                            $username = $request->username[$i];
                            $delete_user = DB::table('user')->where('username', $username);
                            $user = $delete_user->first();
                            if ($user) {
                                $id = $user->username;
                                $deleted = DB::table('user_bank')->where('username', $id)->delete();
                                if ($deleted) {
                                    $successCount++;
                                }
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Account Details Deleted Successfully'
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
            }
        }
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AddBlock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                $admin = $check_user->first();
                if ($check_user->count() == 1) {
                    if (!empty($request->number)) {
                        if (DB::table('block')->where('number', $request->number)->count() == 0) {
                            if ($this->inserting_data('block', ['number' => $request->number, 'date' => $this->system_date(), 'added_by' => $admin->username])) {
                                return response()->json([
                                    'status' => 'success'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable to Add Block Number'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Block Number Added Already'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Block Number Required'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function DeleteBlock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->number)) {
                        $successCount = 0;
                        for ($i = 0; $i < count($request->number); $i++) {
                            $number = $request->number[$i];
                            $delete_block = DB::table('block')->where('number', $number);
                            $block = $delete_block->first();
                            if ($block) {
                                $id = $block->id;
                                $deleted = DB::table('block')->where('id', $id)->delete();
                                if ($deleted) {
                                    $successCount++;
                                }
                            }
                        }
                        if ($successCount > 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Blocked Number Deleted Successfully'
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
                            'message' => 'Block Id Required'
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function Discount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                $database_name = null;
                if ($check_user->count() == 1) {
                    if (isset($request->database_name)) {
                        $database_name = $request->database_name;
                        $search = strtolower($request->search);
                    }

                    if ($database_name == 'wallet_funding') {
                        if (!empty($search)) {
                            return response()->json([
                                'all_stock' => DB::table('wallet_funding')->where(function ($query) use ($search) {
                                $query->orWhere('username', 'LIKE', "%$search%");
                            })->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                            ]);
                        }
                        else {
                            return response()->json([
                                'all_stock' => DB::table('wallet_funding')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                            ]);
                        }
                    }
                    else {
                        return response()->json([
                            'airtime_discount' => DB::table('airtime_discount')->first(),
                            'cable_charges' => DB::table('cable_charge')->first(),
                            'bill_charges' => DB::table('bill_charge')->first(),
                            'cash_discount' => DB::table('cash_discount')->first(),
                            'result_charges' => DB::table('result_charge')->first(),
                            'all_network' => DB::table('network')->get(),
                            'cable_result_lock' => DB::table('cable_result_lock')->first(),

                            'habukhan_api' => DB::table('habukhan_api')->first(),
                            'adex_api' => DB::table('adex_api')->first(),
                            'msorg_api' => DB::table('msorg_api')->first(),
                            'virus_api' => DB::table('virus_api')->first(),
                            'other_api' => (object)array_merge((array)DB::table('other_api')->first(), ['autopilot_key' => DB::table('habukhan_key')->first()->autopilot_key ?? '']),
                            'web_api' => DB::table('web_api')->first(),
                            'airtime_sel' => DB::table('airtime_sel')->first(),
                            'bill_sel' => DB::table('bill_sel')->first(),
                            'cable_sel' => DB::table('cable_sel')->first(),
                            'bulksms_sel' => DB::table('bulksms_sel')->first(),
                            'exam_sel' => DB::table('exam_sel')->first(),
                            'card_settings' => DB::table('card_settings')->where('id', 1)->first(),
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AirtimeDiscount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_vtu_smart' => 'required|numeric|between:0,100',
                        'mtn_vtu_agent' => 'required|numeric|between:0,100',
                        'mtn_vtu_awuf' => 'required|numeric|between:0,100',
                        'mtn_vtu_api' => 'required|numeric|between:0,100',
                        'mtn_vtu_special' => 'required|numeric|between:0,100',
                        // airtel vtu
                        'airtel_vtu_smart' => 'required|numeric|between:0,100',
                        'airtel_vtu_agent' => 'required|numeric|between:0,100',
                        'airtel_vtu_awuf' => 'required|numeric|between:0,100',
                        'airtel_vtu_api' => 'required|numeric|between:0,100',
                        'airtel_vtu_special' => 'required|numeric|between:0,100',
                        //  glo vtu
                        'glo_vtu_smart' => 'required|numeric|between:0,100',
                        'glo_vtu_agent' => 'required|numeric|between:0,100',
                        'glo_vtu_awuf' => 'required|numeric|between:0,100',
                        'glo_vtu_api' => 'required|numeric|between:0,100',
                        'glo_vtu_special' => 'required|numeric|between:0,100',
                        // 9mobile
                        'mobile_vtu_smart' => 'required|numeric|between:0,100',
                        'mobile_vtu_agent' => 'required|numeric|between:0,100',
                        'mobile_vtu_awuf' => 'required|numeric|between:0,100',
                        'mobile_vtu_api' => 'required|numeric|between:0,100',
                        'mobile_vtu_special' => 'required|numeric|between:0,100',

                        // mtn share and sell
                        'mtn_share_smart' => 'required|numeric|between:0,100',
                        'mtn_share_agent' => 'required|numeric|between:0,100',
                        'mtn_share_awuf' => 'required|numeric|between:0,100',
                        'mtn_share_api' => 'required|numeric|between:0,100',
                        'mtn_share_special' => 'required|numeric|between:0,100',
                        // airtel share and sell
                        'airtel_share_smart' => 'required|numeric|between:0,100',
                        'airtel_share_agent' => 'required|numeric|between:0,100',
                        'airtel_share_awuf' => 'required|numeric|between:0,100',
                        'airtel_share_api' => 'required|numeric|between:0,100',
                        'airtel_share_special' => 'required|numeric|between:0,100',
                        //  glo share and sell
                        'glo_share_smart' => 'required|numeric|between:0,100',
                        'glo_share_agent' => 'required|numeric|between:0,100',
                        'glo_share_awuf' => 'required|numeric|between:0,100',
                        'glo_share_api' => 'required|numeric|between:0,100',
                        'glo_share_special' => 'required|numeric|between:0,100',
                        // 9mobile share and sell
                        'mobile_share_smart' => 'required|numeric|between:0,100',
                        'mobile_share_agent' => 'required|numeric|between:0,100',
                        'mobile_share_awuf' => 'required|numeric|between:0,100',
                        'mobile_share_api' => 'required|numeric|between:0,100',
                        'mobile_share_special' => 'required|numeric|between:0,100',

                        // min and max
                        'min_airtime' => 'required|numeric|integer|not_in:0|gt:0',
                        'max_airtime' => 'required|numeric|integer|not_in:0|gt:0'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn_vtu_smart' => $request->mtn_vtu_smart,
                            'mtn_vtu_awuf' => $request->mtn_vtu_awuf,
                            'mtn_vtu_agent' => $request->mtn_vtu_agent,
                            'mtn_vtu_api' => $request->mtn_vtu_api,
                            'mtn_vtu_special' => $request->mtn_vtu_special,
                            // airtel vtu
                            'airtel_vtu_smart' => $request->airtel_vtu_smart,
                            'airtel_vtu_awuf' => $request->airtel_vtu_awuf,
                            'airtel_vtu_agent' => $request->airtel_vtu_agent,
                            'airtel_vtu_api' => $request->airtel_vtu_api,
                            'airtel_vtu_special' => $request->airtel_vtu_special,

                            // glo vtu
                            'glo_vtu_smart' => $request->glo_vtu_smart,
                            'glo_vtu_awuf' => $request->glo_vtu_awuf,
                            'glo_vtu_agent' => $request->glo_vtu_agent,
                            'glo_vtu_api' => $request->glo_vtu_api,
                            'glo_vtu_special' => $request->glo_vtu_special,

                            // 9mobile vtu
                            'mobile_vtu_smart' => $request->mobile_vtu_smart,
                            'mobile_vtu_awuf' => $request->mobile_vtu_awuf,
                            'mobile_vtu_agent' => $request->mobile_vtu_agent,
                            'mobile_vtu_api' => $request->mobile_vtu_api,
                            'mobile_vtu_special' => $request->mobile_vtu_special,

                            // mtn share and sell

                            'mtn_share_smart' => $request->mtn_share_smart,
                            'mtn_share_awuf' => $request->mtn_share_awuf,
                            'mtn_share_agent' => $request->mtn_share_agent,
                            'mtn_share_api' => $request->mtn_share_api,
                            'mtn_share_special' => $request->mtn_share_special,
                            // airtel share ad sell
                            'airtel_share_smart' => $request->airtel_share_smart,
                            'airtel_share_awuf' => $request->airtel_share_awuf,
                            'airtel_share_agent' => $request->airtel_share_agent,
                            'airtel_share_api' => $request->airtel_share_api,
                            'airtel_share_special' => $request->airtel_share_special,

                            // glo share and sell
                            'glo_share_smart' => $request->glo_share_smart,
                            'glo_share_awuf' => $request->glo_share_awuf,
                            'glo_share_agent' => $request->glo_share_agent,
                            'glo_share_api' => $request->glo_share_api,
                            'glo_share_special' => $request->glo_share_special,

                            // 9mobile share and sell
                            'mobile_share_smart' => $request->mobile_share_smart,
                            'mobile_share_awuf' => $request->mobile_share_awuf,
                            'mobile_share_agent' => $request->mobile_share_agent,
                            'mobile_share_api' => $request->mobile_share_api,
                            'mobile_share_special' => $request->mobile_share_special,

                            // max and min

                            'max_airtime' => $request->max_airtime,
                            'min_airtime' => $request->min_airtime
                        ];
                        $updated = DB::table('airtime_discount')->update($data);
                        if ($updated || $updated === 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Updated Successfully'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To Update Airtime Discount'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CableCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if ($request->direct == true || $request->direct == 1) {
                        $main_validator = validator::make($request->all(), [
                            'dstv' => 'required|numeric|integer|not_in:0|gt:0',
                            'gotv' => 'required|numeric|integer|not_in:0|gt:0',
                            'startime' => 'required|numeric|integer|not_in:0|gt:0',
                            'showmax' => 'required|numeric|integer|not_in:0|gt:0',
                        ]);
                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else {
                            $data = [
                                'dstv' => $request->dstv,
                                'gotv' => $request->gotv,
                                'startime' => $request->startime,
                                'showmax' => $request->showmax,
                                'direct' => 1
                            ];
                            $updated = DB::table('cable_charge')->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Successfully'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable To Update Cable Charges'
                                ])->setStatusCode(403);
                            }
                        }
                    }
                    else {
                        $main_validator = validator::make($request->all(), [
                            'dstv' => 'required|numeric|between:0,100',
                            'gotv' => 'required|numeric|between:0,100',
                            'startime' => 'required|numeric|between:0,100',
                            'showmax' => 'required|numeric|between:0,100',
                        ], [
                            'dstv.between' => 'DSTV Charges Must Be Between 0 and 100 (charging in percentage)',
                            'gotv.between' => 'GOTV Charges Must Be Between 0 and 100 (charging in percentage)',
                            'startime.between' => 'STARTIME Charges Must Be Between 0 and 100 (charging in percentage)',
                            'showmax.between' => 'SHOWMAX Charges Must Be Between 0 and 100 (charging in percentage)'
                        ]);

                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else {
                            $data = [
                                'dstv' => $request->dstv,
                                'gotv' => $request->gotv,
                                'startime' => $request->startime,
                                'showmax' => $request->showmax,
                                'direct' => 0
                            ];
                            $updated = DB::table('cable_charge')->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Successfully'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable To Update Cable Charges'
                                ])->setStatusCode(403);
                            }
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BillCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if ($request->direct == true || $request->direct == 1) {
                        $main_validator = validator::make($request->all(), [
                            'bill' => 'required|numeric|integer|not_in:0|gt:0',
                            'bill_max' => 'required|numeric|integer|not_in:0|gt:0',
                            'bill_min' => 'required|numeric|integer|not_in:0|gt:0',
                        ]);
                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else {
                            $data = [
                                'bill' => $request->bill,
                                'bill_max' => $request->bill_max,
                                'bill_min' => $request->bill_min,
                                'direct' => 1
                            ];
                            $updated = DB::table('bill_charge')->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Successfully'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable To Update Bill Charges'
                                ])->setStatusCode(403);
                            }
                        }
                    }
                    else {
                        $main_validator = validator::make($request->all(), [
                            'bill' => 'required|numeric|between:0,100',
                            'bill_max' => 'required|numeric|integer|not_in:0|gt:0',
                            'bill_min' => 'required|numeric|integer|not_in:0|gt:0',
                        ], [
                            'bill.between' => 'Bill Charges Must Be Between 0 and 100 (charging in percentage)'
                        ]);

                        if ($main_validator->fails()) {
                            return response()->json([
                                'message' => $main_validator->errors()->first(),
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                        else {
                            $data = [
                                'bill' => $request->bill,
                                'bill_max' => $request->bill_max,
                                'bill_min' => $request->bill_min,
                                'direct' => 0
                            ];
                            $updated = DB::table('bill_charge')->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated Successfully'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Unable To Update Bill Charges'
                                ])->setStatusCode(403);
                            }
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CashDiscount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        'mtn_number' => 'required|numeric|digits:11',
                        'airtel_number' => 'required|numeric|digits:11',
                        'glo_number' => 'required|numeric|digits:11',
                        'mobile_number' => 'required|numeric|digits:11',
                        'mtn' => 'required|numeric|between:0,100',
                        'airtel' => 'required|numeric|between:0,100',
                        'glo' => 'required|numeric|between:0,100',
                        'mobile' => 'required|numeric|between:0,100'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn' => $request->mtn,
                            'glo' => $request->glo,
                            'airtel' => $request->airtel,
                            'mobile' => $request->mobile,
                            'mtn_number' => $request->mtn_number,
                            'glo_number' => $request->glo_number,
                            'airtel_number' => $request->airtel_number,
                            'mobile_number' => $request->mobile_number,
                        ];
                        $updated = DB::table('cash_discount')->update($data);
                        if ($updated || $updated === 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Updated Successfully'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To Update '
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function ResultCharge(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        'waec' => 'required|numeric|integer|not_in:0|gt:0',
                        'neco' => 'required|numeric|integer|not_in:0|gt:0',
                        'nabteb' => 'required|numeric|integer|not_in:0|gt:0',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'waec' => $request->waec,
                            'neco' => $request->neco,
                            'nabteb' => $request->nabteb,
                        ];
                        $updated = DB::table('result_charge')->updateOrInsert(['id' => 1], $data);
                        if ($updated || $updated === 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Updated Successfully'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Unable To Update result Charges'
                            ])->setStatusCode(403);
                        }
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function OtherCharge(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {

                    $main_validator = validator::make($request->all(), [
                        'bulk_sms' => 'required|numeric|integer|not_in:0|gt:0',
                        'bulk_length' => 'required|numeric|integer|not_in:0|gt:0',
                        'affliate_price' => 'required|numeric|integer|not_in:0|gt:0',
                        'awuf_price' => 'required|numeric|integer|not_in:0|gt:0',
                        'agent_price' => 'required|numeric|integer|not_in:0|gt:0',
                        'monnify_charge' => 'required|numeric|between:0,100',
                        'paystack_charge' => 'required|numeric|min:0',
                        'xixapay_charge' => 'required|numeric|min:0',
                        'paymentpoint_charge' => 'required|numeric|min:0',
                        'earning_min' => 'required|numeric|integer|not_in:0|gt:0',
                        'customer_amount' => 'required|numeric|integer|not_in:0|gt:0',
                        // Card Settings Validation
                        'vcard_ngn_fee' => 'nullable|numeric',
                        'vcard_usd_fee' => 'nullable|numeric',
                        'vcard_usd_rate' => 'nullable|numeric',
                        'vcard_fund_fee' => 'nullable|numeric',
                        'vcard_usd_failed_fee' => 'nullable|numeric',
                        'vcard_ngn_fund_fee' => 'nullable|numeric',
                        'vcard_usd_fund_fee' => 'nullable|numeric',
                        'vcard_ngn_failed_fee' => 'nullable|numeric',
                        // PointWave Charge Settings Validation
                        'pointwave_charge_type' => 'required|in:FLAT,PERCENTAGE',
                        'pointwave_charge_value' => 'required|numeric|min:0',
                        'pointwave_charge_cap' => 'required|numeric|min:0',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'bulk_sms' => $request->bulk_sms,
                            'bulk_length' => $request->bulk_length,
                            'affliate_price' => $request->affliate_price,
                            'awuf_price' => $request->awuf_price,
                            'agent_price' => $request->agent_price,
                            'monnify_charge' => $request->monnify_charge,
                            'paystack_charge' => $request->paystack_charge,
                            'xixapay_charge' => $request->xixapay_charge,
                            'paymentpoint_charge' => $request->paymentpoint_charge,
                            'earning_min' => $request->earning_min,
                            'customer_amount' => $request->customer_amount,
                            'pointwave_charge_type' => strtoupper($request->pointwave_charge_type),
                            'pointwave_charge_value' => $request->pointwave_charge_value,
                            'pointwave_charge_cap' => $request->pointwave_charge_cap,
                        ];
                        DB::table('settings')->update($data);

                        // Update Card Settings
                        $cardData = [];
                        if ($request->has('vcard_ngn_fee'))
                            $cardData['ngn_creation_fee'] = $request->vcard_ngn_fee;
                        if ($request->has('vcard_usd_fee'))
                            $cardData['usd_creation_fee'] = $request->vcard_usd_fee;
                        if ($request->has('vcard_usd_rate'))
                            $cardData['ngn_rate'] = $request->vcard_usd_rate;
                        if ($request->has('vcard_fund_fee'))
                            $cardData['funding_fee_percent'] = $request->vcard_fund_fee;
                        if ($request->has('vcard_usd_failed_fee'))
                            $cardData['usd_failed_tx_fee'] = $request->vcard_usd_failed_fee;
                        if ($request->has('vcard_ngn_fund_fee'))
                            $cardData['ngn_funding_fee_percent'] = $request->vcard_ngn_fund_fee;
                        if ($request->has('vcard_usd_fund_fee'))
                            $cardData['usd_funding_fee_percent'] = $request->vcard_usd_fund_fee;
                        if ($request->has('vcard_ngn_failed_fee'))
                            $cardData['ngn_failed_tx_fee'] = $request->vcard_ngn_failed_fee;

                        if (!empty($cardData)) {
                            DB::table('card_settings')->updateOrInsert(['id' => 1], $cardData);
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Successfully'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function RechargeCardSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn' => $request->mtn,
                            'airtel' => $request->airtel,
                            'glo' => $request->glo,
                            'mobile' => $request->mobile,
                        ];
                        DB::table('recharge_card_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function DataCardSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn' => $request->mtn,
                            'airtel' => $request->airtel,
                            'glo' => $request->glo,
                            'mobile' => $request->mobile,
                        ];
                        DB::table('data_card_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function DataSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_sme' => 'required',
                        'airtel_sme' => 'required',
                        'glo_sme' => 'required',
                        'mobile_sme' => 'required',
                        'mtn_cg' => 'required',
                        'airtel_cg' => 'required',
                        'glo_cg' => 'required',
                        'mobile_cg' => 'required',
                        'mtn_g' => 'required',
                        'airtel_g' => 'required',
                        'glo_g' => 'required',
                        'mobile_g' => 'required',
                        'mtn_sme2' => 'sometimes|required',
                        'airtel_sme2' => 'sometimes|required',
                        'glo_sme2' => 'sometimes|required',
                        'mobile_sme2' => 'sometimes|required',
                        'mtn_datashare' => 'sometimes|required',
                        'airtel_datashare' => 'sometimes|required',
                        'glo_datashare' => 'sometimes|required',
                        'mobile_datashare' => 'sometimes|required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn_sme' => $request->mtn_sme,
                            'airtel_sme' => $request->airtel_sme,
                            'glo_sme' => $request->glo_sme,
                            'mobile_sme' => $request->mobile_sme,

                            'mtn_cg' => $request->mtn_cg,
                            'airtel_cg' => $request->airtel_cg,
                            'glo_cg' => $request->glo_cg,
                            'mobile_cg' => $request->mobile_cg,

                            'mtn_g' => $request->mtn_g,
                            'airtel_g' => $request->airtel_g,
                            'glo_g' => $request->glo_g,
                            'mobile_g' => $request->mobile_g,
                            'mtn_sme2' => $request->mtn_sme2,
                            'airtel_sme2' => $request->airtel_sme2,
                            'glo_sme2' => $request->glo_sme2,
                            'mobile_sme2' => $request->mobile_sme2,
                            'mtn_datashare' => $request->mtn_datashare,
                            'airtel_datashare' => $request->airtel_datashare,
                            'glo_datashare' => $request->glo_datashare,
                            'mobile_datashare' => $request->mobile_datashare
                        ];

                        // Safe filter: remove columns that don't exist in the database
                        $safe_data = [];
                        foreach ($data as $key => $value) {
                            if (Schema::hasColumn('data_sel', $key)) {
                                $safe_data[$key] = $value;
                            }
                        }

                        DB::table('data_sel')->update($safe_data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AirtimeSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn_vtu' => 'required',
                        'airtel_vtu' => 'required',
                        'glo_vtu' => 'required',
                        'mobile_vtu' => 'required',
                        'mtn_share' => 'required',
                        'airtel_share' => 'required',
                        'glo_share' => 'required',
                        'mobile_share' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn_vtu' => $request->mtn_vtu,
                            'airtel_vtu' => $request->airtel_vtu,
                            'glo_vtu' => $request->glo_vtu,
                            'mobile_vtu' => $request->mobile_vtu,

                            'mtn_share' => $request->mtn_share,
                            'airtel_share' => $request->airtel_share,
                            'glo_share' => $request->glo_share,
                            'mobile_share' => $request->mobile_share,
                        ];
                        DB::table('airtime_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CashSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'mtn' => 'required',
                        'airtel' => 'required',
                        'glo' => 'required',
                        'mobile' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'mtn' => $request->mtn,
                            'airtel' => $request->airtel,
                            'glo' => $request->glo,
                            'mobile' => $request->mobile,
                        ];
                        DB::table('cash_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function CableSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'dstv' => 'required',
                        'startime' => 'required',
                        'gotv' => 'required',
                        'showmax' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'startime' => $request->startime,
                            'gotv' => $request->gotv,
                            'dstv' => $request->dstv,
                            'showmax' => $request->showmax,
                        ];
                        DB::table('cable_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BillSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bill' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'bill' => $request->bill,
                        ];
                        DB::table('bill_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BulkSMSsel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bulksms' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'bulksms' => $request->bulksms,
                        ];
                        DB::table('bulksms_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function ExamSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'waec' => 'required',
                        'neco' => 'required',
                        'nabteb' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'waec' => $request->waec,
                            'neco' => $request->neco,
                            'nabteb' => $request->nabteb,
                        ];
                        DB::table('exam_sel')->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function BankTransferSel(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'bank_transfer' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $data = [
                            'bank_transfer' => $request->bank_transfer,
                        ];
                        DB::table('bank_transfer_sel')->update($data);
                        DB::table('settings')->update(['primary_transfer_provider' => $request->bank_transfer]);
                        // FIX: Auto-unlock the selected provider
                        DB::table('transfer_providers')->where('slug', $request->bank_transfer)->update(['is_locked' => 0]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated Success'
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AllUsersInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    if ($request->role == 'ALL' && $request->status == 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role != 'ALL' && $request->status == 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['type' => $request->role])->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role == 'ALL' && $request->status != 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['status' => $request->status])->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role != 'ALL' && $request->status != 'ALL' && empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['status' => $request->status, 'type' => $request->role])->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role == 'ALL' && $request->status == 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('phone', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                        })->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role != 'ALL' && $request->status == 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['type' => $request->role])->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('phone', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                        })->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role == 'ALL' && $request->status != 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['status' => $request->status])->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('phone', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                        })->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else if ($request->role != 'ALL' && $request->status != 'ALL' && !empty($search)) {
                        return response()->json([
                            'all_users' => DB::table('user')->where(['status' => $request->status, 'type' => $request->role])->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('phone', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                        })->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
                    }
                    else {
                        return response()->json([
                            'all_users' => DB::table('user')->select('id', 'name', 'username', 'email', 'pin', 'phone', 'bal', 'refbal', 'kyc', 'status', 'type', 'profile_image', 'date')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AllBankDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    if (!empty($search)) {
                        return response()->json([
                            'autobank' => DB::table('user')->where('autofund', 'ACTIVE')->select('id', 'username', 'profile_image', 'bal', 'refbal', 'status')->orderBy('id', 'desc')->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('phone', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                        })->paginate($request->adex),
                        ]);
                    }
                    else {
                        return response()->json([
                            'autobank' => DB::table('user')->where('autofund', 'ACTIVE')->select('id', 'username', 'profile_image', 'bal', 'refbal', 'status')->orderBy('id', 'desc')->paginate($request->adex),
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function UserBankAccountD(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    if (!empty($search)) {
                        return response()->json([
                            'userbank' => DB::table('user_bank')->where(function ($query) use ($search) {
                            $query->orWhere('username', 'LIKE', "%$search%");
                        })->orderBy('id', 'desc')->paginate($request->adex)
                        ]);
                    }
                    else {
                        return response()->json([
                            'userbank' => DB::table('user_bank')->orderBy('id', 'desc')->paginate($request->adex)
                        ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AllUserBanned(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    return response()->json([
                        'autobanned' => DB::table('block')->leftJoin("user", function ($join) {
                        $join->on("user.username", "=", "block.added_by");
                    })->orderBy('block.id', 'desc')->get(),
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }
    public function AllSystemPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    return response()->json([
                        'data_plans' => DB::table('data_plan')->leftJoin("user", function ($join) {
                        $join->on("user.username", "=", "data_plan.added_by");
                    })->orderBy('data_plan.id', 'desc')->get(),
                        'cable_plans' => DB::table('cable_plan')->leftJoin("user", function ($join) {
                        $join->on("user.username", "=", "cable_plan.added_by");
                    })->orderBy('cable_plan.id', 'desc')->get(),
                        'bill_plans' => DB::table('bill_plan')->leftJoin("user", function ($join) {
                        $join->on("user.username", "=", "bill_plan.added_by");
                    })->orderBy('bill_plan.id', 'desc')->get(),
                        'result_plans' => DB::table('stock_result_pin')->leftJoin("user", function ($join) {
                        $join->on("user.username", "=", "stock_result_pin.added_by");
                    })->orderBy('stock_result_pin.id', 'desc')->get(),
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }

    public function ApiBalance(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $habukhan_api = DB::table('habukhan_api')->first();
                    $api_website = DB::table('web_api')->first();
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $api_website->habukhan_website1 . "/api/user/");
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt(
                        $ch,
                        CURLOPT_HTTPHEADER,
                    [
                        "Authorization: Basic " . base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password),
                    ]
                    );
                    $json = curl_exec($ch);
                    curl_close($ch);
                    $decode_habukhan = json_decode($json, true);
                    if (isset($decode_habukhan)) {
                        if (isset($decode_habukhan['status'])) {
                            if ($decode_habukhan['status'] == 'success') {
                                $admin_balance = '₦' . $decode_habukhan['balance'];
                            }
                            else {
                                $admin_balance = 'API NOT CONNECTED';
                            }
                        }
                        else {
                            $admin_balance = 'API NOT CONNECTED';
                        }
                    }
                    else {
                        $admin_balance = 'API NOT CONNECTED';
                    }
                    return response()->json([
                        'status' => 'success',
                        'admin_url' => $api_website->habukhan_website1,
                        'balance' => $admin_balance
                    ]);
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
        else {
            return redirect(config('app.error_500'));
        }
    }

    public function lockVirtualAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $validator = Validator::make($request->all(), [
                        'provider' => 'required|in:palmpay,monnify,wema,xixapay,pointwave',
                        'enabled' => 'required|boolean'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first()
                        ], 400);
                    }

                    $provider = $request->provider;
                    $enabled = $request->enabled;

                    // Check if trying to disable all providers
                    if (!$enabled) {
                        $settings = DB::table('settings')->first();
                        $enabledCount = 0;
                        if ($settings->palmpay_enabled)
                            $enabledCount++;
                        if ($settings->monnify_enabled)
                            $enabledCount++;
                        if ($settings->wema_enabled)
                            $enabledCount++;
                        if ($settings->xixapay_enabled)
                            $enabledCount++;
                        if ($settings->pointwave_enabled ?? false)
                            $enabledCount++;

                        if ($enabledCount <= 1) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Cannot disable all providers. At least one must remain enabled.'
                            ], 400);
                        }
                    }

                    // Update the provider status
                    $column = $provider . '_enabled';
                    DB::table('settings')->update([$column => $enabled]);

                    return response()->json([
                        'status' => 'success',
                        'message' => ucfirst($provider) . ' has been ' . ($enabled ? 'enabled' : 'disabled')
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unauthorized'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function setDefaultVirtualAccount(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });

                if ($check_user->count() > 0) {
                    $validator = Validator::make($request->all(), [
                        'default_provider' => 'required|in:palmpay,monnify,wema,xixapay,pointwave'
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first()
                        ], 400);
                    }

                    $provider = $request->default_provider;

                    // Check if the provider is enabled
                    $settings = DB::table('settings')->first();
                    $column = $provider . '_enabled';

                    if (!$settings->$column) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot set a disabled provider as default. Please enable it first.'
                        ], 400);
                    }

                    // Update the default provider
                    DB::table('settings')->update(['default_virtual_account' => $provider]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Default provider set to ' . ucfirst($provider)
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unauthorized'
                    ])->setStatusCode(403);
                }
            }
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    // SMART TRANSFER ROUTER METHODS

    public function getTransferSettings(Request $request, $id = null)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $token = $id ?: $request->id;
            $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($token)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                $settings = DB::table('settings')->select('transfer_lock_all', 'transfer_charge_type', 'transfer_charge_value', 'transfer_charge_cap')->first();
                $providers = DB::table('transfer_providers')->orderBy('priority', 'asc')->get();

                return response()->json([
                    'status' => 'success',
                    'providers' => $providers,
                    'settings' => $settings
                ]);
            }
            else {
                \Log::error('Check User Failed', ['id' => $token, 'verified' => $this->verifytoken($token)]);
                return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Invalid Origin'])->setStatusCode(403);
    }

    public function lockTransferProvider(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                // $request->slug (e.g., 'paystack'), $request->action ('lock' or 'unlock')

                // FIX: Prevent locking the primary provider
                if ($request->action == 'lock') {
                    $currentPrimary = DB::table('settings')->value('primary_transfer_provider');
                    if ($currentPrimary === $request->slug) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cannot lock the active Primary Provider (' . ucfirst($currentPrimary) . '). Please select a different primary provider first.'
                        ]);
                    }
                }

                $is_locked = ($request->action == 'lock') ? 1 : 0;
                DB::table('transfer_providers')->where('slug', $request->slug)->update(['is_locked' => $is_locked]);

                return response()->json([
                    'status' => 'success',
                    'message' => ucfirst($request->slug) . ' has been ' . ($is_locked ? 'Locked' : 'Unlocked')
                ]);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function setTransferPriority(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0 && is_array($request->priorities)) {
                // Expects array like: [['slug' => 'xixapay', 'priority' => 1], ['slug' => 'monnify', 'priority' => 2]]
                foreach ($request->priorities as $p) {
                    DB::table('transfer_providers')->where('slug', $p['slug'])->update(['priority' => $p['priority']]);
                }
                return response()->json(['status' => 'success', 'message' => 'Priorities Updated']);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function updateTransferCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                DB::table('settings')->update([
                    'transfer_charge_type' => $request->type, // FLAT or PERCENT
                    'transfer_charge_value' => $request->value,
                    'transfer_charge_cap' => $request->cap // Optional max charge for percent
                ]);
                return response()->json(['status' => 'success', 'message' => 'Transfer Charges Updated']);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function toggleGlobalTransferLock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(['type' => 'ADMIN']);
            if ($check_user->count() > 0) {
                $lock = ($request->action == 'lock') ? 1 : 0;
                DB::table('settings')->update(['transfer_lock_all' => $lock]);
                return response()->json(['status' => 'success', 'message' => 'Global Transfer Lock ' . ($lock ? 'Enabled' : 'Disabled')]);
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
    public function DeleteKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) { // Admin ID
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $target_user_id = $request->user_id;
                    if (!$target_user_id) {
                        return response()->json(['status' => 403, 'message' => 'Target User ID Required'])->setStatusCode(403);
                    }

                    DB::table('user_kyc')->where('user_id', $target_user_id)->delete();

                    DB::table('user')->where('id', $target_user_id)->update([
                        'kyc' => '0',
                        'nin' => null,
                        'bvn' => null,
                        'dob' => null,
                        'xixapay_kyc_data' => null,
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'User KYC Deleted Successfully'
                    ]);
                }
                else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
            else {
                return response()->json(['status' => 403, 'message' => 'Unable to Authenticate System'])->setStatusCode(403);
            }
        }
        else {
            return response()->json(['status' => 403, 'message' => 'Unable to Authenticate System'])->setStatusCode(403);
        }
    }

    public function UpdateDiscountOther(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // Update Card Settings
                    if ($request->has('card_ngn_creation_fee')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['ngn_creation_fee' => $request->card_ngn_creation_fee]);
                    }
                    if ($request->has('card_usd_creation_fee')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['usd_creation_fee' => $request->card_usd_creation_fee]);
                    }
                    if ($request->has('card_ngn_rate')) {
                        DB::table('card_settings')->updateOrInsert(['id' => 1], ['ngn_rate' => $request->card_ngn_rate]);
                    }

                    // Update PointWave Charge Settings
                    $pointwaveUpdates = [];
                    if ($request->has('pointwave_charge_type')) {
                        $chargeType = strtoupper($request->pointwave_charge_type);
                        if (in_array($chargeType, ['FLAT', 'PERCENTAGE'])) {
                            $pointwaveUpdates['pointwave_charge_type'] = $chargeType;
                        }
                    }
                    if ($request->has('pointwave_charge_value')) {
                        $pointwaveUpdates['pointwave_charge_value'] = max(0, floatval($request->pointwave_charge_value));
                    }
                    if ($request->has('pointwave_charge_cap')) {
                        $pointwaveUpdates['pointwave_charge_cap'] = max(0, floatval($request->pointwave_charge_cap));
                    }

                    if (!empty($pointwaveUpdates)) {
                        DB::table('settings')->where('id', 1)->update($pointwaveUpdates);
                    }

                    return response()->json(['status' => 'success', 'message' => 'Settings Updated']);
                }
                else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unable to Authenticate'])->setStatusCode(403);
    }

    public function getCardSettings(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $settings = DB::table('card_settings')->where('id', 1)->first();
                    return response()->json(['status' => 'success', 'data' => $settings]);
                }
                else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unable to Authenticate'])->setStatusCode(403);
    }
    public function AllUsersKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $search = strtolower($request->search);
                    $status = $request->status ?? 'ALL';

                    // 1. Get verified records from user_kyc
                    $kycRecords = DB::table('user_kyc')
                        ->join('user', 'user_kyc.user_id', '=', 'user.id')
                        ->select(
                        'user_kyc.id',
                        'user_kyc.user_id',
                        'user_kyc.id_type',
                        'user_kyc.id_number',
                        'user_kyc.status',
                        'user_kyc.provider',
                        'user_kyc.created_at as submitted_at',
                        'user_kyc.verified_at',
                        'user_kyc.full_response_json',
                        'user.username',
                        'user.name',
                        'user.email',
                        'user.phone',
                        'user.profile_image',
                        'user.address',
                        'user.id_card_path',
                        'user.utility_bill_path',
                        'user.type as user_type',
                        DB::raw("'verified' as kyc_source"),
                        DB::raw("CONCAT('@', user.username) as display_user")
                    );

                    // 2. Get pending/submitted records from user table (Smart KYC flow)
                    $userSubmissions = DB::table('user')
                        ->whereNotNull('kyc_submitted_at')
                        ->select(
                        'user.id as id',
                        'user.id as user_id',
                        DB::raw("'N/A' as id_type"),
                        DB::raw("COALESCE(user.bvn, user.nin, 'N/A') as id_number"),
                        'user.kyc_status as status',
                        DB::raw("'xixapay' as provider"),
                        'user.kyc_submitted_at as submitted_at',
                        DB::raw("NULL as verified_at"),
                        'user.xixapay_kyc_data as full_response_json',
                        'user.username',
                        'user.name',
                        'user.email',
                        'user.phone',
                        'user.profile_image',
                        'user.address',
                        'user.id_card_path',
                        'user.utility_bill_path',
                        'user.type as user_type',
                        DB::raw("'user_table' as kyc_source"),
                        DB::raw("CONCAT('@', user.username) as display_user")
                    );

                    // Combine and Filter
                    $query = $kycRecords->union($userSubmissions);

                    // Wrap in a subquery to allow filtering and sorting on the union
                    $finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_kyc"))
                        ->mergeBindings($query)
                        ->orderBy('submitted_at', 'desc');

                    if ($status != 'ALL') {
                        $finalQuery->where('status', $status);
                    }

                    if (!empty($search)) {
                        $finalQuery->where(function ($q) use ($search) {
                            $q->orWhere('username', 'LIKE', "%$search%")
                                ->orWhere('name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('id_number', 'LIKE', "%$search%");
                        });
                    }

                    return response()->json([
                        'all_kyc' => $finalQuery->paginate($request->input('habukhan', 15)),
                    ]);
                }
                else {
                    return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function ApproveUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_admin = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN')->first();
                if ($check_admin) {
                    $userId = $request->user_id ?? $request->kyc_id; // Frontend might send either
                    $source = $request->kyc_source ?? 'verified';

                    if ($source === 'user_table' || (!$request->kyc_source && $userId)) {
                        DB::table('user')->where('id', $userId)->update([
                            'kyc_status' => 'approved',
                            'kyc' => '1'
                        ]);
                        $targetUser = DB::table('user')->where('id', $userId)->first();
                        (new \App\Services\NotificationService())->sendKycStatusNotification($targetUser, 'approved');
                    }

                    // Also check user_kyc table if kyc_id is explicitly provided or if it's the legacy flow
                    if ($request->kyc_id) {
                        $kyc = DB::table('user_kyc')->where('id', $request->kyc_id)->first();
                        if ($kyc) {
                            DB::table('user_kyc')->where('id', $request->kyc_id)->update(['status' => 'verified', 'verified_at' => now()]);
                            DB::table('user')->where('id', $kyc->user_id)->update(['kyc' => '1', 'kyc_status' => 'approved']);
                            $targetUser = DB::table('user')->where('id', $kyc->user_id)->first();
                            (new \App\Services\NotificationService())->sendKycStatusNotification($targetUser, 'approved');
                        }
                        else if ($userId) {
                            // Try treating kyc_id as user_id for user_kyc
                            DB::table('user_kyc')->where('user_id', $userId)->update(['status' => 'verified', 'verified_at' => now()]);
                        }
                    }

                    return response()->json(['status' => 'success', 'message' => 'KYC Approved Successfully']);
                }
            }
        }
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
    }

    public function RejectUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_admin = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN')->first();
                if ($check_admin) {
                    $userId = $request->user_id ?? $request->kyc_id;
                    $source = $request->kyc_source ?? 'verified';

                    if ($source === 'user_table' || (!$request->kyc_source && $userId)) {
                        DB::table('user')->where('id', $userId)->update([
                            'kyc_status' => 'rejected',
                            'kyc' => '0'
                        ]);
                        $targetUser = DB::table('user')->where('id', $userId)->first();
                        (new \App\Services\NotificationService())->sendKycStatusNotification($targetUser, 'rejected', $request->reason ?? 'Documents invalid');
                    }

                    if ($request->kyc_id) {
                        $kyc = DB::table('user_kyc')->where('id', $request->kyc_id)->first();
                        if ($kyc) {
                            DB::table('user_kyc')->where('id', $request->kyc_id)->update(['status' => 'rejected']);
                            DB::table('user')->where('id', $kyc->user_id)->update(['kyc' => '0', 'kyc_status' => 'rejected']);
                            $targetUser = DB::table('user')->where('id', $kyc->user_id)->first();
                            (new \App\Services\NotificationService())->sendKycStatusNotification($targetUser, 'rejected', $request->reason ?? 'Documents invalid');
                        }
                        else if ($userId) {
                            DB::table('user_kyc')->where('user_id', $userId)->update(['status' => 'rejected']);
                        }
                    }

                    return response()->json(['status' => 'success', 'message' => 'KYC Rejected Successfully']);
                }
            }
        }
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
    }

    public function DeleteUserKyc(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    $kyc_id = $request->kyc_id ?? $request->id_kyc ?? $request->target_id;

                    // 1. Try deleting from user_kyc (Legacy)
                    $kyc = DB::table('user_kyc')->where('id', $kyc_id)->first();
                    if ($kyc) {
                        $userId = $kyc->user_id;
                        DB::table('user_kyc')->where('id', $kyc_id)->delete();
                        DB::table('user')->where('id', $userId)->update([
                            'kyc' => '0',
                            'kyc_status' => 'pending',
                            'kyc_submitted_at' => null,
                            'nin' => null,
                            'bvn' => null,
                            'dob' => null,
                            'id_card_path' => null,
                            'utility_bill_path' => null,
                            'xixapay_kyc_data' => null,
                        ]);
                        return response()->json(['status' => 'success', 'message' => 'KYC Deleted Successfully']);
                    }

                    // 2. Try resetting using id as user_id (Smart KYC Flow)
                    $user = DB::table('user')->where('id', $kyc_id)->first();
                    if ($user) {
                        DB::table('user')->where('id', $kyc_id)->update([
                            'kyc' => '0',
                            'kyc_status' => 'pending',
                            'kyc_submitted_at' => null,
                            'nin' => null,
                            'bvn' => null,
                            'dob' => null,
                            'id_card_path' => null,
                            'utility_bill_path' => null,
                            'xixapay_kyc_data' => null,
                        ]);
                        // Also cleanup user_kyc just in case
                        DB::table('user_kyc')->where('user_id', $kyc_id)->delete();
                        return response()->json(['status' => 'success', 'message' => 'User KYC Deleted Successfully']);
                    }

                    return response()->json(['status' => 'error', 'message' => 'KYC Record or User Not Found (ID: ' . $kyc_id . ')'], 404);
                }
            }
        }
    }

    public function AllVirtualCards(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');
                if ($check_user->count() > 0) {
                    $page = $request->query('page', 0);
                    $rowsPerPage = $request->query('rowsPerPage', 10);
                    $search = $request->query('search', '');

                    $query = DB::table('virtual_cards')
                        ->join('user', 'virtual_cards.user_id', '=', 'user.id')
                        ->select('virtual_cards.*', 'user.username', 'user.email');

                    if (!empty($search)) {
                        $query->where(function ($q) use ($search) {
                            $q->where('user.username', 'like', "%$search%")
                                ->orWhere('virtual_cards.card_id', 'like', "%$search%")
                                ->orWhere('virtual_cards.card_pan', 'like', "%$search%");
                        });
                    }

                    $total = $query->count();
                    $cards = $query->orderBy('virtual_cards.created_at', 'desc')
                        ->offset($page * $rowsPerPage)
                        ->limit($rowsPerPage)
                        ->get();

                    return response()->json([
                        'status' => 'success',
                        'data' => $cards,
                        'total' => $total
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminTerminateCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    $provider = new \App\Services\Banking\Providers\XixapayProvider();
                    $result = $provider->changeCardStatus($cardId, 'blocked');

                    if ($result['status'] === 'success') {
                        DB::table('virtual_cards')->where('card_id', $cardId)->update([
                            'status' => 'terminated',
                            'updated_at' => now()
                        ]);
                        return response()->json(['status' => 'success', 'message' => 'Card Terminated Successfully']);
                    }
                    return response()->json(['status' => 'error', 'message' => $result['message']], 400);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminDebitCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $amount = $request->amount;

                    if ($amount <= 0)
                        return response()->json(['status' => 'error', 'message' => 'Invalid amount'], 400);

                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    $provider = new \App\Services\Banking\Providers\XixapayProvider();
                    $result = $provider->withdrawVirtualCard($cardId, $amount);

                    if ($result['status'] === 'success') {
                        // Log Transaction
                        DB::table('card_transactions')->insert([
                            'card_id' => $cardId,
                            'xixapay_transaction_id' => 'ADMIN_DEBIT_' . time(),
                            'amount' => $amount,
                            'currency' => $card->card_type,
                            'status' => 'success',
                            'merchant_name' => 'Admin Debit/Withdrawal',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        return response()->json(['status' => 'success', 'message' => 'Card Debited Successfully']);
                    }
                    return response()->json(['status' => 'error', 'message' => $result['message']], 400);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminDeleteCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');
                if ($check_user->count() > 0) {
                    $cardId = $request->card_id;
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();

                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);
                    if ($card->status !== 'terminated')
                        return response()->json(['status' => 'error', 'message' => 'Only terminated cards can be deleted'], 400);

                    DB::table('virtual_cards')->where('card_id', $cardId)->delete();
                    return response()->json(['status' => 'success', 'message' => 'Card Record Deleted Successfully']);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }

    public function AdminCardCustomerInfo(Request $request, $cardId)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where('type', 'ADMIN');
                if ($check_user->count() > 0) {
                    $card = DB::table('virtual_cards')->where('card_id', $cardId)->first();
                    if (!$card)
                        return response()->json(['status' => 'error', 'message' => 'Card not found'], 404);

                    $user = DB::table('user')->where('id', $card->user_id)->first();
                    // Fetch latest balance from provider
                    $provider = new \App\Services\Banking\Providers\XixapayProvider();
                    $details = $provider->getCardDetails($cardId);

                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'user' => $user,
                            'card' => $card,
                            'provider_details' => $details['data'] ?? null
                        ]
                    ]);
                }
            }
        }
        return response()->json(['status' => 403, 'message' => 'Unauthorized'])->setStatusCode(403);
    }
}