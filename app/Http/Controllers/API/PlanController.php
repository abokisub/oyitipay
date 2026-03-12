<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{

    public function DataPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();

                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'network_type' => 'required',
                    ]);
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    }
                    else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    }
                    else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    }
                    else if ($adex->type == 'API') {
                        $user_type = 'api';
                    }
                    else {
                        $user_type = 'special';
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $get_network = DB::table('network')->where('plan_id', $request->network)->first();
                        $all_plan = DB::table('data_plan')->where(['network' => $get_network->network, 'plan_type' => $request->network_type, 'plan_status' => 1]);
                        if ($all_plan->count() > 0) {
                            foreach ($all_plan->get() as $adex => $plan) {
                                $data_plan[] = ['name' => $plan->plan_name . $plan->plan_size . ' ' . $plan->plan_type . ' = ₦' . number_format($plan->$user_type, 2) . ' ' . $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => $plan->$user_type];
                                ;
                            }
                        }
                        else {
                            $data_plan = [];
                        }
                        return response()->json([
                            'status' => 'success',
                            'plan' => $data_plan
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
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
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
    public function CablePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (isset($request->id)) {
                $cable_name = DB::table('cable_id')->where('plan_id', $request->id)->first();
                return response()->json([
                    'status' => 'suucess',
                    'plan' => DB::table('cable_plan')->where(['cable_name' => $cable_name->cable_name, 'plan_status' => 1])->select('cable_name', 'plan_name', 'plan_price', 'plan_id')->get()
                ]);
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
    public function CableCharges(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (isset($request->id)) {
                if (DB::table('cable_plan')->where('plan_id', $request->id)->count() == 1) {
                    $cable = DB::table('cable_plan')->where('plan_id', $request->id)->first();
                    $amount = $cable->plan_price;
                    $cable_name = strtolower($cable->cable_name);
                    $cable_setting = DB::table('cable_charge')->first();
                    if ($cable_setting->direct == 1) {
                        $charges = $cable_setting->$cable_name;
                    }
                    else {
                        $charges = ($amount / 100) * $cable_setting->$cable_name;
                    }
                    return response()->json([
                        'status' => 'suucess',
                        'amount' => $amount,
                        'charges' => $charges
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Unable to calculate'
                    ])->setStatusCode(403);
                }
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
    public function DataList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    }
                    else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    }
                    else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    }
                    else if ($adex->type == 'API') {
                        $user_type = 'api';
                    }
                    else {
                        $user_type = 'special';
                    }


                    $all_plan = DB::table('data_plan')->where(['plan_status' => 1]);
                    if ($all_plan->count() > 0) {
                        foreach ($all_plan->get() as $adex => $plan) {
                            $data_plan[] = ['plan_name' => $plan->plan_name . $plan->plan_size, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2), 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'network' => $plan->network];
                            ;
                        }
                    }
                    else {
                        $data_plan = [];
                    }
                    return response()->json([
                        'status' => 'success',
                        'plan' => $data_plan
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
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
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
    public function CableList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {




            return response()->json([
                'status' => 'success',
                'plan' => DB::table('cable_plan')->where('plan_status', 1)->select('cable_name', 'plan_name', 'plan_price', 'plan_id')->get()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DiscoList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {




            return response()->json([
                'status' => 'success',
                'plan' => DB::table('bill_plan')->where('plan_status', 1)->select('disco_name', 'plan_id')->get()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function ExamList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $exam_list = [];
            $exam_id = DB::table('exam_id')->get();
            $exam_price = DB::table('result_charge')->first();

            foreach ($exam_id as $exam) {
                $amount = '0.00';
                if ($exam_price) {
                    $exam_key = strtolower($exam->exam_name);
                    // Standardize mapping for mixed cases (WAEC -> waec, NECO -> neco)
                    if (isset($exam_price->$exam_key)) {
                        $amount = number_format($exam_price->$exam_key, 2);
                    }
                }

                $exam_list[] = [
                    'exam_name' => $exam->exam_name,
                    'plan_id' => $exam->plan_id,
                    'amount' => '₦' . $amount
                ];
            }

            return response()->json([
                'status' => 'success',
                'plan' => $exam_list
            ]);
        }
        else {
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function HomeData(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            return response()->json([
                'status' => 'success',
                'mtn' => DB::table('data_plan')->where(['network' => 'MTN', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'glo' => DB::table('data_plan')->where(['network' => 'GLO', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'airtel' => DB::table('data_plan')->where(['network' => 'AIRTEL', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get(),
                'mobile' => DB::table('data_plan')->where(['network' => '9MOBILE', 'plan_status' => 1])->select('plan_name', 'network', 'plan_size', 'plan_day', 'smart')->orderBy('smart', 'asc')->get()
            ]);
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function DataCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    }
                    else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    }
                    else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    }
                    else if ($adex->type == 'API') {
                        $user_type = 'api';
                    }
                    else {
                        $user_type = 'special';
                    }


                    $all_plan = DB::table('data_card_plan')->where(['plan_status' => 1]);
                    if ($all_plan->count() > 0) {
                        foreach ($all_plan->get() as $adex => $plan) {
                            $data_plan[] = ['plan_name' => $plan->name . $plan->plan_size, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2), 'plan_type' => $plan->plan_type, 'plan_day' => $plan->plan_day, 'network' => $plan->network, 'load_pin' => $plan->load_pin, 'check_balance' => $plan->check_balance];
                            ;
                        }
                    }
                    else {
                        $data_plan = [];
                    }
                    return response()->json([
                        'status' => 'success',
                        'plan' => $data_plan
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
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
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

    public function RechargeCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $requestId = $request->id ?? $request->route('id');
            if (!empty($requestId)) {
                $userId = $this->verifyapptoken($requestId) ?? $this->verifytoken($requestId);
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $userId]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    }
                    else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    }
                    else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    }
                    else if ($adex->type == 'API') {
                        $user_type = 'api';
                    }
                    else {
                        $user_type = 'special';
                    }


                    $all_plan = DB::table('recharge_card_plan')->where(['plan_status' => 1]);
                    if ($all_plan->count() > 0) {
                        foreach ($all_plan->get() as $adex => $plan) {
                            $data_plan[] = ['name' => $plan->name, 'plan_id' => $plan->plan_id, 'amount' => number_format($plan->$user_type, 2), 'network' => $plan->network, 'load_pin' => $plan->load_pin, 'check_balance' => $plan->check_balance];
                            ;
                        }
                    }
                    else {
                        $data_plan = [];
                    }
                    return response()->json([
                        'status' => 'success',
                        'plan' => $data_plan
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
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
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