<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\MailController;

class SecureController extends Controller
{
    private function verifyOrigin(Request $request)
    {
        $allowed = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');

        // Allow if no origin (could be same domain or tool), or if in whitelist
        if (!$origin || in_array($origin, $allowed) || $origin === $request->getSchemeAndHttpHost()) {
            return true;
        }

        return false;
    }

    public function Airtimelock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifyapptoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // network vtu
                    if ($request->mtn_vtu == true || $request->mtn_vtu == 1) {
                        $mtn_vtu = 1;
                    }
                    else {
                        $mtn_vtu = 0;
                    }
                    if ($request->glo_vtu == true || $request->glo_vtu == 1) {
                        $glo_vtu = 1;
                    }
                    else {
                        $glo_vtu = 0;
                    }
                    if ($request->airtel_vtu == true || $request->airtel_vtu == 1) {
                        $airtel_vtu = 1;
                    }
                    else {
                        $airtel_vtu = 0;
                    }
                    if ($request->mobile_vtu == true || $request->mobile_vtu == 1) {
                        $mobile_vtu = 1;
                    }
                    else {
                        $mobile_vtu = 0;
                    }

                    // airtime share and sell
                    if ($request->mtn_share == true || $request->mtn_share == 1) {
                        $mtn_share = 1;
                    }
                    else {
                        $mtn_share = 0;
                    }
                    if ($request->glo_share == true || $request->glo_share == 1) {
                        $glo_share = 1;
                    }
                    else {
                        $glo_share = 0;
                    }
                    if ($request->airtel_share == true || $request->airtel_share == 1) {
                        $airtel_share = 1;
                    }
                    else {
                        $airtel_share = 0;
                    }
                    if ($request->mobile_share == true || $request->mobile_share == 1) {
                        $mobile_share = 1;
                    }
                    else {
                        $mobile_share = 0;
                    }

                    // airtime 2 cash
                    if ($request->mtn_cash == true || $request->mtn_cash == 1) {
                        $mtn_cash = 1;
                    }
                    else {
                        $mtn_cash = 0;
                    }
                    if ($request->glo_cash == true || $request->glo_cash == 1) {
                        $glo_cash = 1;
                    }
                    else {
                        $glo_cash = 0;
                    }
                    if ($request->mobile_cash == true || $request->mobile_cash == 1) {
                        $mobile_cash = 1;
                    }
                    else {
                        $mobile_cash = 0;
                    }
                    if ($request->airtel_cash == true || $request->airtel_cash == 1) {
                        $airtel_cash = 1;
                    }
                    else {
                        $airtel_cash = 0;
                    }
                    $mtn_data = [
                        'network_vtu' => $mtn_vtu,
                        'network_share' => $mtn_share,
                        'cash' => $mtn_cash
                    ];
                    $glo_data = [
                        'network_vtu' => $glo_vtu,
                        'network_share' => $glo_share,
                        'cash' => $glo_cash,
                    ];
                    $airtel_data = [
                        'network_vtu' => $airtel_vtu,
                        'network_share' => $airtel_share,
                        'cash' => $airtel_cash
                    ];
                    $mobile_data = [
                        'network_vtu' => $mobile_vtu,
                        'network_share' => $mobile_share,
                        'cash' => $mobile_cash
                    ];
                    $this->updateData($mtn_data, 'network', ['network' => 'MTN']);
                    $this->updateData($glo_data, 'network', ['network' => 'GLO']);
                    $this->updateData($mobile_data, 'network', ['network' => '9MOBILE']);
                    $this->updateData($airtel_data, 'network', ['network' => 'AIRTEL']);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated'
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
    public function DataLock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // data sme
                    if ($request->mtn_sme == true || $request->mtn_sme == 1) {
                        $mtn_sme = 1;
                    }
                    else {
                        $mtn_sme = 0;
                    }
                    if ($request->glo_sme == true || $request->glo_sme == 1) {
                        $glo_sme = 1;
                    }
                    else {
                        $glo_sme = 0;
                    }
                    if ($request->airtel_sme == true || $request->airtel_sme == 1) {
                        $airtel_sme = 1;
                    }
                    else {
                        $airtel_sme = 0;
                    }
                    if ($request->mobile_sme == true || $request->mobile_sme == 1) {
                        $mobile_sme = 1;
                    }
                    else {
                        $mobile_sme = 0;
                    }
                    // sme 2
                    if ($request->mtn_sme2 == true || $request->mtn_sme2 == 1) {
                        $mtn_sme2 = 1;
                    }
                    else {
                        $mtn_sme2 = 0;
                    }
                    if ($request->glo_sme2 == true || $request->glo_sme2 == 1) {
                        $glo_sme2 = 1;
                    }
                    else {
                        $glo_sme2 = 0;
                    }
                    if ($request->airtel_sme2 == true || $request->airtel_sme2 == 1) {
                        $airtel_sme2 = 1;
                    }
                    else {
                        $airtel_sme2 = 0;
                    }
                    if ($request->mobile_sme2 == true || $request->mobile_sme2 == 1) {
                        $mobile_sme2 = 1;
                    }
                    else {
                        $mobile_sme2 = 0;
                    }
                    // data cg
                    if ($request->mtn_cg == true || $request->mtn_cg == 1) {
                        $mtn_cg = 1;
                    }
                    else {
                        $mtn_cg = 0;
                    }
                    if ($request->glo_cg == true || $request->glo_cg == 1) {
                        $glo_cg = 1;
                    }
                    else {
                        $glo_cg = 0;
                    }
                    if ($request->airtel_cg == true || $request->airtel_cg == 1) {
                        $airtel_cg = 1;
                    }
                    else {
                        $airtel_cg = 0;
                    }
                    if ($request->mobile_cg == true || $request->mobile_cg == 1) {
                        $mobile_cg = 1;
                    }
                    else {
                        $mobile_cg = 0;
                    }

                    // g

                    if ($request->mtn_g == true || $request->mtn_g == 1) {
                        $mtn_g = 1;
                    }
                    else {
                        $mtn_g = 0;
                    }
                    if ($request->glo_g == true || $request->glo_g == 1) {
                        $glo_g = 1;
                    }
                    else {
                        $glo_g = 0;
                    }
                    if ($request->airtel_g == true || $request->airtel_g == 1) {
                        $airtel_g = 1;
                    }
                    else {
                        $airtel_g = 0;
                    }
                    if ($request->mobile_g == true || $request->mobile_g == 1) {
                        $mobile_g = 1;
                    }
                    else {
                        $mobile_g = 0;
                    }

                    // datashare
                    if ($request->mtn_datashare == true || $request->mtn_datashare == 1) {
                        $mtn_datashare = 1;
                    }
                    else {
                        $mtn_datashare = 0;
                    }
                    if ($request->glo_datashare == true || $request->glo_datashare == 1) {
                        $glo_datashare = 1;
                    }
                    else {
                        $glo_datashare = 0;
                    }
                    if ($request->airtel_datashare == true || $request->airtel_datashare == 1) {
                        $airtel_datashare = 1;
                    }
                    else {
                        $airtel_datashare = 0;
                    }
                    if ($request->mobile_datashare == true || $request->mobile_datashare == 1) {
                        $mobile_datashare = 1;
                    }
                    else {
                        $mobile_datashare = 0;
                    }

                    $mtn_data = [
                        'network_sme' => $mtn_sme,
                        'network_cg' => $mtn_cg,
                        'network_g' => $mtn_g,
                        'network_sme2' => $mtn_sme2,
                        'network_datashare' => $mtn_datashare
                    ];
                    $glo_data = [
                        'network_sme' => $glo_sme,
                        'network_cg' => $glo_cg,
                        'network_g' => $glo_g,
                        'network_sme2' => $glo_sme2,
                        'network_datashare' => $glo_datashare
                    ];
                    $airtel_data = [
                        'network_sme' => $airtel_sme,
                        'network_cg' => $airtel_cg,
                        'network_g' => $airtel_g,
                        'network_sme2' => $airtel_sme2,
                        'network_datashare' => $airtel_datashare
                    ];
                    $mobile_data = [
                        'network_sme' => $mobile_sme,
                        'network_cg' => $mobile_cg,
                        'network_g' => $mobile_g,
                        'network_sme2' => $mobile_sme2,
                        'network_datashare' => $mobile_datashare
                    ];

                    // Safe filter: remove columns that don't exist in the database
                    $mtn_data = array_filter($mtn_data, function ($key) {
                        return Schema::hasColumn('network', $key);
                    }, ARRAY_FILTER_USE_KEY);
                    $glo_data = array_filter($glo_data, function ($key) {
                        return Schema::hasColumn('network', $key);
                    }, ARRAY_FILTER_USE_KEY);
                    $airtel_data = array_filter($airtel_data, function ($key) {
                        return Schema::hasColumn('network', $key);
                    }, ARRAY_FILTER_USE_KEY);
                    $mobile_data = array_filter($mobile_data, function ($key) {
                        return Schema::hasColumn('network', $key);
                    }, ARRAY_FILTER_USE_KEY);

                    $this->updateData($mtn_data, 'network', ['network' => 'MTN']);
                    $this->updateData($glo_data, 'network', ['network' => 'GLO']);
                    $this->updateData($mobile_data, 'network', ['network' => '9MOBILE']);
                    $this->updateData($airtel_data, 'network', ['network' => 'AIRTEL']);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated'
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
    public function CableLock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // network vtu
                    if ($request->dstv == true || $request->dstv == 1) {
                        $dstv = 1;
                    }
                    else {
                        $dstv = 0;
                    }
                    if ($request->startime == true || $request->startime == 1) {
                        $startime = 1;
                    }
                    else {
                        $startime = 0;
                    }
                    if ($request->gotv == true || $request->gotv == 1) {
                        $gotv = 1;
                    }
                    else {
                        $gotv = 0;
                    }
                    if ($request->showmax == true || $request->showmax == 1) {
                        $showmax = 1;
                    }
                    else {
                        $showmax = 0;
                    }

                    $data = [
                        'dstv' => $dstv,
                        'gotv' => $gotv,
                        'startime' => $startime,
                        'showmax' => $showmax,
                    ];

                    $updated = DB::table('cable_result_lock')->update($data);
                    if ($updated !== false) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'messgae' => 'Unable to update'
                        ])->setStatusCode(403);
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
    public function ResultLock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // network vtu
                    if ($request->waec == true || $request->waec == 1) {
                        $waec = 1;
                    }
                    else {
                        $waec = 0;
                    }
                    if ($request->neco == true || $request->neco == 1) {
                        $neco = 1;
                    }
                    else {
                        $neco = 0;
                    }
                    if ($request->nabteb == true || $request->nabteb == 1) {
                        $nabteb = 1;
                    }
                    else {
                        $nabteb = 0;
                    }

                    $data = [
                        'waec' => $waec,
                        'neco' => $neco,
                        'nabteb' => $nabteb,
                    ];

                    if (DB::table('cable_result_lock')->update($data)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'messgae' => 'Unable to update'
                        ])->setStatusCode(403);
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
    public function OtherLock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifyapptoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // network vtu
                    if ($request->monnify_atm == true || $request->monnify_atm == 1) {
                        $monnify_atm = 1;
                    }
                    else {
                        $monnify_atm = 0;
                    }
                    if ($request->monnify == true || $request->monnify == 1) {
                        $monnify = 1;
                    }
                    else {
                        $monnify = 0;
                    }
                    if ($request->referral == true || $request->referral == 1) {
                        $referral = 1;
                    }
                    else {
                        $referral = 0;
                    }
                    if ($request->bank_transfer == true || $request->bank_transfer == 1) {
                        $bank_transfer = 1;
                    }
                    else {
                        $bank_transfer = 0;
                    }
                    if ($request->paystack == true || $request->paystack == 1) {
                        $paystack = 1;
                    }
                    else {
                        $paystack = 0;
                    }
                    if ($request->is_verify_email == true || $request->is_verify_email == 1) {
                        $is_verify_email = 1;
                    }
                    else {
                        $is_verify_email = 0;
                    }
                    if ($request->is_feature == true || $request->is_feature == 1) {
                        $is_feature = 1;
                    }
                    else {
                        $is_feature = 0;
                    }
                    if ($request->wema == true || $request->wema == 1) {
                        $wema = 1;
                    }
                    else {
                        $wema = 0;
                    }
                    if ($request->kolomoni_mfb == true || $request->kolomoni_mfb == 1) {
                        $kolomoni_mfb = 1;
                    }
                    else {
                        $kolomoni_mfb = 0;
                    }
                    if ($request->fed == true || $request->fed == 1) {
                        $fed = 1;
                    }
                    else {
                        $fed = 0;
                    }
                    if ($request->str == true || $request->str == 1) {
                        $str = 1;
                    }
                    else {
                        $str = 0;
                    }
                    if ($request->bulksms == true || $request->bulksms == 1) {
                        $bulksms = 1;
                    }
                    else {
                        $bulksms = 0;
                    }
                    if ($request->allow_pin == true || $request->allow_pin == 1) {
                        $allow_pin = 1;
                    }
                    else {
                        $allow_pin = 0;
                    }
                    if ($request->bill == true || $request->bill == 1) {
                        $bill_lock = 1;
                    }
                    else {
                        $bill_lock = 0;
                    }
                    if ($request->allow_limit == true || $request->allow_limit == 1) {
                        $allow_limit = 1;
                    }
                    else {
                        $allow_limit = 0;
                    }

                    if ($request->stock == true || $request->stock == 1) {
                        $stock = 1;
                    }
                    else {
                        $stock = 0;
                    }

                    if ($request->card_ngn_lock == true || $request->card_ngn_lock == 1) {
                        $card_ngn_lock = 1;
                    }
                    else {
                        $card_ngn_lock = 0;
                    }

                    if ($request->card_usd_lock == true || $request->card_usd_lock == 1) {
                        $card_usd_lock = 1;
                    }
                    else {
                        $card_usd_lock = 0;
                    }

                    $data = [
                        'monnify_atm' => $monnify_atm,
                        'monnify' => $monnify,
                        'referral' => $referral,
                        'is_verify_email' => $is_verify_email,
                        'is_feature' => $is_feature,
                        'wema' => $wema,
                        'kolomoni_mfb' => $kolomoni_mfb,
                        'fed' => $fed,
                        'str' => $str,
                        'bulksms' => $bulksms,
                        'allow_pin' => $allow_pin,
                        'bill' => $bill_lock,
                        'bank_transfer' => $bank_transfer,
                        'paystack' => $paystack,
                        'allow_limit' => $allow_limit,
                        'stock' => $stock,
                        'card_ngn_lock' => $card_ngn_lock,
                        'card_usd_lock' => $card_usd_lock,
                    ];

                    if (DB::table('settings')->update($data)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'messgae' => 'Unable to update'
                        ])->setStatusCode(403);
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
    public function DataPlanDelete(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->plan_id)) {
                        for ($i = 0; $i < count($request->plan_id); $i++) {
                            $plan_id = $request->plan_id[$i];
                            DB::table('data_plan')->where('plan_id', $plan_id)->delete();
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Data Plan Deleted'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Data Plan Id Required'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AddDataPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_name' => 'required|numeric',
                        'plan_size' => 'required',
                        'network' => 'required',
                        'plan_type' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric',
                        'plan_day' => 'required',
                    ], [
                        'smart.required' => 'Smart Price Required',
                        'awuf.required' => 'Awuf Price Required',
                        'agent.required' => 'Agent Price Required',
                        'api.required' => 'Api Price Required',
                        'special.required' => 'Special Price Required',

                        'smart.numeric' => 'Smart Price Must Be Numeric',
                        'awuf.numeric' => 'Awuf Price Must Be Numeric',
                        'agent.numeric' => 'Agent Price Must Be Numeric',
                        'api.numeric' => 'Api Price Must Be Numeric',
                        'special.numeric' => 'Special Price Must Be Numeric'
                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        //  plan id
                        $check_plans = DB::table('data_plan');
                        if ($check_plans->count() > 0) {
                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                            $plan_id_get = $last_plan_id->plan_id;
                            $plan_id = $plan_id_get + 1;
                        }
                        else {
                            $plan_id = 1;
                        }
                        // insertind data here
                        $data = [
                            'network' => $request->network,
                            'plan_name' => $request->plan_name,
                            'plan_size' => $request->plan_size,
                            'plan_type' => $request->plan_type,
                            'plan_status' => $plan_status,
                            'plan_id' => $plan_id,
                            'smart' => $request->smart,
                            'awuf' => $request->awuf,
                            'agent' => $request->agent,
                            'special' => $request->special,
                            'plan_day' => $request->plan_day,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'msorg1' => $request->msorg1,
                            'msorg2' => $request->msorg2,
                            'msorg3' => $request->msorg3,
                            'msorg4' => $request->msorg4,
                            'msorg5' => $request->msorg5,
                            'virus1' => $request->virus1,
                            'virus2' => $request->virus2,
                            'virus3' => $request->virus3,
                            'virus4' => $request->virus4,
                            'virus5' => $request->virus5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'free4' => $request->free4,
                            'free5' => $request->free5,
                            'simserver' => $request->simserver,
                            'simhosting' => $request->simhosting,
                            'msplug' => $request->msplug,
                            'smeplug' => $request->smeplug,
                            'ogdamns' => $request->ogdamns,
                            'added_by' => $added_by,
                            'easyaccess' => $request->easyaccess,
                            'megasub' => $request->megasub,
                            'megasubcloud' => $request->megasubcloud,
                            'adex1' => $request->adex1,
                            'adex2' => $request->adex2,
                            'adex3' => $request->adex3,
                            'adex4' => $request->adex4,
                            'adex5' => $request->adex5,
                            'boltnet' => $request->boltnet,
                            'zimrax' => $request->zimrax,
                            'hamdala' => $request->hamdala,
                            'autopilot' => $request->autopilot
                        ];
                        if (DB::table('data_plan')->where('plan_id', $plan_id)->count() == 0) {
                            if ($this->inserting_data('data_plan', $data)) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Data Plan Inserted'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Try Again Later Or Contact Habukhan Developers'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'message' => 'Try Again Later Or Contact Habukhan Developers',
                                'status' => 403
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
    public function RDataPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (DB::table('data_plan')->where('plan_id', $request->plan_id)->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'plan' => DB::table('data_plan')->where('plan_id', $request->plan_id)->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Plan ID'
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
    public function EditDataPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_name' => 'required|numeric',
                        'plan_size' => 'required',
                        'network' => 'required',
                        'plan_type' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric',
                        'plan_id' => 'required',
                        'plan_day' => 'required'
                    ], [
                        'smart.required' => 'Smart Price Required',
                        'awuf.required' => 'Awuf Price Required',
                        'agent.required' => 'Agent Price Required',
                        'api.required' => 'Api Price Required',
                        'special.required' => 'Special Price Required',

                        'smart.numeric' => 'Smart Price Must Be Numeric',
                        'awuf.numeric' => 'Awuf Price Must Be Numeric',
                        'agent.numeric' => 'Agent Price Must Be Numeric',
                        'api.numeric' => 'Api Price Must Be Numeric',
                        'special.numeric' => 'Special Price Must Be Numeric'

                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    elseif (DB::table('data_plan')->where('plan_id', $request->plan_id)->count() !== 1) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid  Pla ID'
                        ])->setStatusCode(403);
                    }
                    else {
                        // update plan id
                        $data = [
                            'network' => $request->network,
                            'plan_name' => $request->plan_name,
                            'plan_size' => $request->plan_size,
                            'plan_type' => $request->plan_type,
                            'plan_day' => $request->plan_day,
                            'plan_status' => $plan_status,
                            'smart' => $request->smart,
                            'awuf' => $request->awuf,
                            'agent' => $request->agent,
                            'special' => $request->special,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'msorg1' => $request->msorg1,
                            'msorg2' => $request->msorg2,
                            'msorg3' => $request->msorg3,
                            'msorg4' => $request->msorg4,
                            'msorg5' => $request->msorg5,
                            'virus1' => $request->virus1,
                            'virus2' => $request->virus2,
                            'virus3' => $request->virus3,
                            'virus4' => $request->virus4,
                            'virus5' => $request->virus5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'free4' => $request->free4,
                            'free5' => $request->free5,
                            'simserver' => $request->simserver,
                            'simhosting' => $request->simhosting,
                            'msplug' => $request->msplug,
                            'smeplug' => $request->smeplug,
                            'ogdamns' => $request->ogdamns,
                            'easyaccess' => $request->easyaccess,
                            'megasub' => $request->megasub,
                            'megasubcloud' => $request->megasubcloud,
                            'adex1' => $request->adex1,
                            'adex2' => $request->adex2,
                            'adex3' => $request->adex3,
                            'adex4' => $request->adex4,
                            'adex5' => $request->adex5,
                            'boltnet' => $request->boltnet,
                            'zimrax' => $request->zimrax,
                            'hamdala' => $request->hamdala,
                            'autopilot' => $request->autopilot
                        ];
                        $updated = DB::table('data_plan')->where('plan_id', $request->plan_id)->update($data);
                        if ($updated || $updated === 0) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Updated Success'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'You Didnt Make any Changes'
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
    public function DeleteCablePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->plan_id)) {
                        for ($i = 0; $i < count($request->plan_id); $i++) {
                            $plan_id = $request->plan_id[$i];
                            DB::table('cable_plan')->where('plan_id', $plan_id)->delete();
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Cable Plan Deleted'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Cable Plan Id Required'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RCablePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (DB::table('cable_plan')->where('plan_id', $request->plan_id)->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'plan' => DB::table('cable_plan')->where('plan_id', $request->plan_id)->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Plan ID'
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
    public function AddCablePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_name' => 'required',
                        'cable_name' => 'required',
                        'plan_price' => 'required|numeric'
                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        //  plan id
                        $check_plans = DB::table('cable_plan');
                        if ($check_plans->count() > 0) {
                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                            $plan_id_get = $last_plan_id->plan_id;
                            $plan_id = $plan_id_get + 1;
                        }
                        else {
                            $plan_id = 1;
                        }
                        // insertind data here
                        $data = [
                            'cable_name' => $request->cable_name,
                            'plan_price' => $request->plan_price,
                            'plan_status' => $plan_status,
                            'plan_name' => $request->plan_name,
                            'plan_id' => $plan_id,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'vtpass' => $request->vtpass,
                            'autopilot' => $request->autopilot,
                            'added_by' => $added_by
                        ];
                        if (DB::table('cable_plan')->where('plan_id', $plan_id)->count() == 0) {
                            if ($this->inserting_data('cable_plan', $data)) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Cable Plan Inserted'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Try Again Later Or Contact Habukhan Developers'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'message' => 'Try Again Later Or Contact Habukhan Developers',
                                'status' => 403
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
    public function EditCablePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_name' => 'required',
                        'cable_name' => 'required',
                        'plan_price' => 'required|numeric',
                        'plan_id' => 'required'
                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if (DB::table('cable_plan')->where('plan_id', $request->plan_id)->count() !== 1) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Plan ID'
                        ])->setStatusCode(403);
                    }
                    else {
                        // insertind data here
                        $data = [
                            'cable_name' => $request->cable_name,
                            'plan_price' => $request->plan_price,
                            'plan_status' => $plan_status,
                            'plan_name' => $request->plan_name,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'vtpass' => $request->vtpass,
                            'autopilot' => $request->autopilot
                        ];
                        $updated = DB::table('cable_plan')->where('plan_id', $request->plan_id)->update($data);
                        if ($updated || $updated === 0) {

                            return response()->json([
                                'status' => 'success',
                                'message' => 'Update Success'
                            ]);
                        }
                        else {
                            return response()->json([
                                'message' => 'No Changes Made',
                                'status' => 403
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
    public function DeleteBillPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->plan_id)) {
                        for ($i = 0; $i < count($request->plan_id); $i++) {
                            $plan_id = $request->plan_id[$i];
                            DB::table('bill_plan')->where('plan_id', $plan_id)->delete();
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Disco Plan Deleted'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Disco Plan Id Required'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RBillPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (DB::table('bill_plan')->where('plan_id', $request->plan_id)->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'plan' => DB::table('bill_plan')->where('plan_id', $request->plan_id)->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Plan ID'
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
    public function CreateBillPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'disco_name' => 'required',
                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        //  plan id
                        $check_plans = DB::table('bill_plan');
                        if ($check_plans->count() > 0) {
                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                            $plan_id_get = $last_plan_id->plan_id;
                            $plan_id = $plan_id_get + 1;
                        }
                        else {
                            $plan_id = 1;
                        }
                        // insertind data here
                        $data = [
                            'disco_name' => $request->disco_name,
                            'plan_status' => $plan_status,
                            'plan_id' => $plan_id,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'vtpass' => $request->vtpass,
                            'added_by' => $added_by
                        ];
                        if (DB::table('bill_plan')->where('plan_id', $plan_id)->count() == 0) {
                            if ($this->inserting_data('bill_plan', $data)) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Bill Plan Inserted'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Try Again Later Or Contact Habukhan Developers'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'message' => 'Try Again Later Or Contact Habukhan Developers',
                                'status' => 403
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
    public function EditBillPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'disco_name' => 'required',
                        'plan_id' => 'required|string'
                    ]);

                    // plan status
                    if ($request->plan_status == true || $request->plan_status == 1) {
                        $plan_status = 1;
                    }
                    else {
                        $plan_status = 0;
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        // insertind data here
                        $data = [
                            'disco_name' => $request->disco_name,
                            'plan_status' => $plan_status,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'vtpass' => $request->vtpass,
                            'added_by' => $added_by
                        ];
                        if (DB::table('bill_plan')->where('plan_id', $request->plan_id)->count() == 1) {
                            $updated = DB::table('bill_plan')->where('plan_id', $request->plan_id)->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'No Changes Made'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'message' => 'Invalid Plan ID',
                                'status' => 403
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RNetwork(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (DB::table('network')->where('plan_id', $request->plan_id)->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'plan' => DB::table('network')->where('plan_id', $request->plan_id)->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Plan ID'
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
    public function EditeNetwork(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // admin username
                    $admin_d = $check_user->first();
                    $added_by = $admin_d->username;
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'plan_id' => 'required|string'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        // insertind data here
                        $data = [
                            'habukhan_id' => $request->habukhan_id,
                            'msorg_id' => $request->msorg_id,
                            'virus_id' => $request->virus_id,
                            'boltnet_id' => $request->boltnet_id,
                            'autopilot_id' => $request->autopilot_id,
                        ];
                        if (DB::table('network')->where(['plan_id' => $request->plan_id, 'network' => $request->network])->count() == 1) {
                            $updated = DB::table('network')->where(['plan_id' => $request->plan_id, 'network' => $request->network])->update($data);
                            if ($updated || $updated === 0) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Updated'
                                ]);
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'No Changes Made'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'message' => 'Invalid Plan ID',
                                'status' => 403
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditHabukhanApi(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'habukhan1_username' => $request->habukhan1_username,
                        'habukhan1_password' => $request->habukhan1_password,
                        'habukhan2_username' => $request->habukhan2_username,
                        'habukhan2_password' => $request->habukhan2_password,
                        'habukhan3_username' => $request->habukhan3_username,
                        'habukhan3_password' => $request->habukhan3_password,
                        'habukhan4_username' => $request->habukhan4_username,
                        'habukhan4_password' => $request->habukhan4_password,
                        'habukhan5_username' => $request->habukhan5_username,
                        'habukhan5_password' => $request->habukhan5_password
                    ];
                    $updated = DB::table('habukhan_api')->update($data);
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditAdexApi(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'adex1_username' => $request->adex1_username,
                        'adex1_password' => $request->adex1_password,
                        'adex2_username' => $request->adex2_username,
                        'adex2_password' => $request->adex2_password,
                        'adex3_username' => $request->adex3_username,
                        'adex3_password' => $request->adex3_password,
                        'adex4_username' => $request->adex4_username,
                        'adex4_password' => $request->adex4_password,
                        'adex5_username' => $request->adex5_username,
                        'adex5_password' => $request->adex5_password
                    ];
                    $updated = DB::table('adex_api')->update($data);
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditMsorgApi(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'msorg1' => $request->msorg1,
                        'msorg2' => $request->msorg2,
                        'msorg3' => $request->msorg3,
                        'msorg4' => $request->msorg4,
                        'msorg5' => $request->msorg5
                    ];
                    $updated = DB::table('msorg_api')->update($data);
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditVirusApi(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'virus1' => $request->virus1,
                        'virus2' => $request->virus2,
                        'virus3' => $request->virus3,
                        'virus4' => $request->virus4,
                        'virus5' => $request->virus5
                    ];
                    $updated = DB::table('virus_api')->update($data);
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditOtherApi(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'simserver' => $request->simserver,
                        'smeplug' => $request->smeplug,
                        'msplug' => $request->msplug,
                        'ogdamns' => $request->ogdamns,
                        'simhosting' => $request->simhosting,
                        'vtpass_username' => $request->vtpass_username,
                        'vtpass_password' => $request->vtpass_password,
                        'hollatag_username' => $request->hollatag_username,
                        'hollatag_password' => $request->hollatag_password,
                        'easy_access' => $request->easy_access,
                        'boltnet_token' => $request->boltnet_token,
                        'zimrax' => $request->zimrax,
                        'hamdala' => $request->hamdala
                    ];
                    $updated = DB::table('other_api')->update($data);
                    if (isset($request->autopilot_key)) {
                        $upd_key = DB::table('habukhan_key')->update(['autopilot_key' => $request->autopilot_key]);
                        if ($upd_key) {
                            $updated = true;
                        }
                    }
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditWebUrl(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $data = [
                        'habukhan_website1' => $request->habukhan_website1,
                        'habukhan_website2' => $request->habukhan_website2,
                        'habukhan_website3' => $request->habukhan_website3,
                        'habukhan_website4' => $request->habukhan_website4,
                        'habukhan_website5' => $request->habukhan_website5,
                        'msorg_website1' => $request->msorg_website1,
                        'msorg_website2' => $request->msorg_website2,
                        'msorg_website3' => $request->msorg_website3,
                        'msorg_website4' => $request->msorg_website4,
                        'msorg_website5' => $request->msorg_website5,
                        'virus_website1' => $request->virus_website1,
                        'virus_website2' => $request->virus_website2,
                        'virus_website3' => $request->virus_website3,
                        'virus_website4' => $request->virus_website4,
                        'virus_website5' => $request->virus_website5,
                        'adex_website1' => $request->adex_website1,
                        'adex_website2' => $request->adex_website2,
                        'adex_website3' => $request->adex_website3,
                        'adex_website4' => $request->adex_website4,
                        'adex_website5' => $request->adex_website5,
                        'boltnet_url' => $request->boltnet_url
                    ];
                    $updated = DB::table('web_api')->update($data);
                    if ($updated || $updated === 0) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'No Changes Made'
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
        }
        else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RResult(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    // validate form
                    $main_validator = validator::make($request->all(), [
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (DB::table('stock_result_pin')->where('plan_id', $request->plan_id)->count() == 1) {
                            return response()->json([
                                'status' => 'success',
                                'plan' => DB::table('stock_result_pin')->where('plan_id', $request->plan_id)->first()
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Invalid Plan ID'
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
    public function AddResult(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $added_by = $habukhan->username;
                    $main_validator = validator::make($request->all(), [
                        'exam_name' => 'required',
                        'pin' => 'required',
                        'serial' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        // plan status

                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        }
                        else {
                            $plan_status = 0;
                        }
                        $pin = explode(',', $request->pin);
                        $serial = explode(',', $request->serial);
                        for ($i = 0; $i < count($pin); $i++) {
                            $load_pin = $pin[$i];
                            $j = $i;
                            for ($a = 0; $a < count($serial); $a++) {
                                $load_serial = $serial[$a];
                                if ($j == $a) {
                                    if (DB::table('stock_result_pin')->where(['exam_name' => $request->exam_name, 'exam_pin' => $load_pin, 'exam_serial' => $load_serial])->count() == 0) {
                                        //  plan id
                                        $check_plans = DB::table('stock_result_pin');
                                        if ($check_plans->count() > 0) {
                                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                                            $plan_id_get = $last_plan_id->plan_id;
                                            $plan_id = $plan_id_get + 1;
                                        }
                                        else {
                                            $plan_id = 1;
                                        }
                                        $data = [
                                            'exam_name' => $request->exam_name,
                                            'exam_pin' => $load_pin,
                                            'exam_serial' => $load_serial,
                                            'plan_status' => $plan_status,
                                            'added_by' => $added_by,
                                            'added_date' => $this->system_date(),
                                            'plan_id' => $plan_id
                                        ];
                                        $this->inserting_data('stock_result_pin', $data);
                                    }
                                    else {
                                        return response()->json([
                                            'status' => 403,
                                            'message' => 'Result Pin Added Already'
                                        ])->setStatusCode(403);
                                    }
                                }
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
    public function DelteResult(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (isset($request->plan_id)) {
                        for ($i = 0; $i < count($request->plan_id); $i++) {
                            $plan_id = $request->plan_id[$i];
                            DB::table('stock_result_pin')->where('plan_id', $plan_id)->delete();
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Result Checker Pin Deleted'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Plan Id Required'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditResult(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $added_by = $habukhan->username;
                    $main_validator = validator::make($request->all(), [
                        'exam_name' => 'required',
                        'pin' => 'required',
                        'serial' => 'required',
                        'plan_id' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else if (DB::table('stock_result_pin')->where('plan_id', $request->plan_id)->count() != 1) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Invalid Plan ID'
                        ])->setStatusCode(403);
                    }
                    else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        }
                        else {
                            $plan_status = 0;
                        }

                        $data = [
                            'exam_name' => $request->exam_name,
                            'exam_pin' => $request->pin,
                            'exam_serial' => $request->serial,
                            'plan_status' => $plan_status,
                        ];
                        DB::table('stock_result_pin')->where('plan_id', $request->plan_id)->update($data);
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
    public function UserStock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $username = $habukhan->username;

                    return response()->json([
                        'status' => 'success',
                        'stock' => DB::table('wallet_funding')->where('username', $username)->first()
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
    public function UserEditStock(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $username = $habukhan->username;
                    // mtn status
                    if ($request->mtn_sme == true || $request->mtn_sme == 1) {
                        $mtn_sme = 1;
                    }
                    else {
                        $mtn_sme = 0;
                    }
                    if ($request->mtn_cg == true || $request->mtn_cg == 1) {
                        $mtn_cg = 1;
                    }
                    else {
                        $mtn_cg = 0;
                    }
                    if ($request->mtn_g == true || $request->mtn_g == 1) {
                        $mtn_g = 1;
                    }
                    else {
                        $mtn_g = 0;
                    }

                    //glo stataus

                    if ($request->glo_sme == true || $request->glo_sme == 1) {
                        $glo_sme = 1;
                    }
                    else {
                        $glo_sme = 0;
                    }
                    if ($request->glo_cg == true || $request->glo_cg == 1) {
                        $glo_cg = 1;
                    }
                    else {
                        $glo_cg = 0;
                    }
                    if ($request->glo_g == true || $request->glo_g == 1) {
                        $glo_g = 1;
                    }
                    else {
                        $glo_g = 0;
                    }

                    // airtel status
                    if ($request->airtel_sme == true || $request->airtel_sme == 1) {
                        $airtel_sme = 1;
                    }
                    else {
                        $airtel_sme = 0;
                    }
                    if ($request->airtel_cg == true || $request->airtel_cg == 1) {
                        $airtel_cg = 1;
                    }
                    else {
                        $airtel_cg = 0;
                    }
                    if ($request->airtel_g == true || $request->airtel_g == 1) {
                        $airtel_g = 1;
                    }
                    else {
                        $airtel_g = 0;
                    }
                    //9mobile status
                    if ($request->mobile_sme == true || $request->mobile_sme == 1) {
                        $mobile_sme = 1;
                    }
                    else {
                        $mobile_sme = 0;
                    }
                    if ($request->mobile_cg == true || $request->mobile_cg == 1) {
                        $mobile_cg = 1;
                    }
                    else {
                        $mobile_cg = 0;
                    }
                    if ($request->mobile_g == true || $request->mobile_g == 1) {
                        $mobile_g = 1;
                    }
                    else {
                        $mobile_g = 0;
                    }
                    $data = [
                        'mtn_sme' => $mtn_sme,
                        'mtn_cg' => $mtn_cg,
                        'mtn_g' => $mtn_g,
                        'airtel_sme' => $airtel_sme,
                        'airtel_cg' => $airtel_cg,
                        'airtel_g' => $airtel_g,
                        'glo_sme' => $glo_sme,
                        'glo_cg' => $glo_cg,
                        'glo_g' => $glo_g,
                        'mobile_sme' => $mobile_sme,
                        'mobile_cg' => $mobile_cg,
                        'mobile_g' => $mobile_g
                    ];
                    DB::table('wallet_funding')->where('username', $username)->update($data);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'update success'
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
    public function UserProfile(Request $request)
    {
        if ($this->verifyOrigin($request)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $username = $habukhan->username;
                    $main_validator = validator::make($request->all(), []);
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
                    else {
                        $data = [
                            'address' => $request->address,
                            'about' => $request->about,
                            'profile_image' => $path,
                            'webhook' => $request->webhook
                        ];
                        DB::table('user')->where(['username' => $username, 'id' => $habukhan->id])->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Updated success'
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
    public function ResetPasswordUser(Request $request)
    {

        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $username = $habukhan->username;
                    $main_validator = validator::make($request->all(), [
                        'oldPassword' => 'required',
                        'newPassword' => "required",
                        'confirmNewPassword' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $hash = substr(sha1(md5($request->oldPassword)), 3, 10);
                        $mdpass = md5($request->oldPassword);
                        if ((password_verify($request->oldPassword, $habukhan->password)) xor ($request->oldPassword == $habukhan->password) xor ($hash == $habukhan->password) xor ($mdpass == $habukhan->password)) {
                            $password = password_hash($request->newPassword, PASSWORD_DEFAULT, array('cost' => 16));
                            DB::table('user')->where(['username' => $username, 'id' => $habukhan->id])->update(['password' => $password]);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'password updated'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Incorrect Old Password'
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
    public function ChangePin(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $habukhan = $check_user->first();
                    $username = $habukhan->username;
                    $main_validator = validator::make($request->all(), [
                        'oldpin' => 'required',
                        'newpin' => "required|numeric|digits:4",
                        'confirmpin' => 'required|numeric',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (($request->oldpin == $habukhan->pin)) {
                            DB::table('user')->where(['username' => $username, 'id' => $habukhan->id])->update(['pin' => $request->newpin]);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Transaction Pin updated'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Incorrect Old Transaction Pin'
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
    public function CreatePin(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    $username = $adex->username;
                    $main_validator = validator::make($request->all(), [
                        'newpin' => "required|numeric|digits:4",
                        'confirmpin' => 'required|numeric',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        if (($adex->pin == null || $adex->pin == '')) {
                            DB::table('user')->where(['username' => $username, 'id' => $adex->id])->update(['pin' => $request->newpin]);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Transaction Pin Created'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'You Are Not Authorized Kindly Reload the Page'
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
    public function UserAccountDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    $username = $adex->username;
                    $main_validator = validator::make($request->all(), [
                        'bank_name' => 'required',
                        'bank_code' => 'required',
                        'account_number' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        // monnify verify account details

                        $send_request = "https://api.monnify.com/api/v1/disbursements/account/validate?accountNumber=$request->account_number&bankCode=$request->bank_code";
                        $json_response = json_decode(@file_get_contents($send_request), true);
                        if (!empty($json_response)) {
                            if ($json_response['requestSuccessful'] == true) {
                                if (DB::table('user_bank')->where('username', $username)->count() == 1) {
                                    DB::table('user_bank')->where('username', $username)->update(['bank' => $request->bank_name, 'bank_name' => $json_response['responseBody']['accountName'], 'bank_code' => $request->bank_code, 'account_number' => $request->account_number]);
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Updated Success'
                                    ]);
                                }
                                else {
                                    DB::table('user_bank')->insert(['bank' => $request->bank_name, 'bank_name' => $json_response['responseBody']['accountName'], 'bank_code' => $request->bank_code, 'account_number' => $request->account_number, 'username' => $username, 'date' => $this->system_date()]);
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Updated Success'
                                    ]);
                                }
                            }
                            else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Inavlid Account Details'
                                ])->setStatusCode(403);
                            }
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'Inavlid Account Details'
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
    public function UsersAccountDetails(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)]);
                if ($check_user->count() == 1) {
                    $adex = $check_user->first();
                    $username = $adex->username;

                    if (DB::table('user_bank')->where('username', $username)->count() == 1) {
                        return response()->json([
                            'status' => 'success',
                            'bank' => DB::table('user_bank')->where('username', $username)->first()
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'not yet availabale'
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
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DataPurchased(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url) || $request->headers->get('origin') === $request->getSchemeAndHttpHost()) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    // Sum the amount spent on data purchases today
                    $total_amount = DB::table('data')
                        ->where(['username' => $user->username, 'plan_status' => 1])
                        ->whereDate('plan_date', Carbon::today())
                        ->sum('amount');
                    // Calculate total data volume purchased today
                    $data_purchase = DB::table('data')
                        ->where(['username' => $user->username, 'plan_status' => 1])
                        ->whereDate('plan_date', Carbon::today())
                        ->get();
                    $total_gb = 0;
                    foreach ($data_purchase as $data) {
                        $plans = $data->plan_name;
                        $check_gb = substr($plans, -2);
                        if ($check_gb == 'MB') {
                            $mb = rtrim($plans, "MB");
                            $gb = $mb / 1024;
                        }
                        elseif ($check_gb == 'GB') {
                            $gb = rtrim($plans, "GB");
                        }
                        elseif ($check_gb == 'TB') {
                            $tb = rtrim($plans, 'TB');
                            $gb = ceil($tb * 1024);
                        }
                        else {
                            $gb = 0;
                        }
                        $total_gb += $gb;
                    }
                    if ($total_gb >= 1024) {
                        $calculate_gb = $total_gb / 1024 . 'TB';
                    }
                    else {
                        $calculate_gb = $total_gb . 'GB';
                    }
                    return response()->json([
                        'status' => 'success',
                        'data_purchased_amount' => $total_amount,
                        'data_purchased_volume' => $calculate_gb
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'invalid user id'
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
    public function StockBalance(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    return response()->json([
                        'stock_balance' => DB::table('wallet_funding')->where(['username' => $user->username])->get()
                    ]);
                }
                else {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'invalid user id'
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
    public function SOFTWARE(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {


            return response()->json([
                'status' => 'success',
                'app' => DB::table('app_download')->get()
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
    public function SystemInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        $update = [
                            'app_name' => $request->app_name,
                            'app_phone' => $request->app_phone,
                            'app_email' => $request->app_email,
                            'app_address' => $request->app_address,
                            'instagram' => $request->instagram,
                            'facebook' => $request->facebook,
                            'tiktok' => $request->tiktok
                        ];
                        DB::table('general')->update($update);

                        $settingsUpdate = [
                            'playstore_url' => $request->playstore_url,
                            'appstore_url' => $request->appstore_url,
                            'app_update_title' => $request->update_title,
                            'app_update_desc' => $request->update_desc
                        ];
                        DB::table('settings')->update($settingsUpdate);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function SytemMessage(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        $data = [];
                        if ($request->has('notif_message'))
                            $data['notif_message'] = $request->notif_message;
                        if ($request->has('notfi_show') || $request->has('notif_show')) {
                            $data['notif_show'] = ($request->notfi_show == 1 || $request->notif_show == true) ? 1 : 0;
                        }
                        if ($request->hasFile('ads_image')) {
                            $image = $request->file('ads_image');
                            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                            if (!file_exists(public_path('uploads/ads'))) {
                                mkdir(public_path('uploads/ads'), 0755, true);
                            }
                            $image->move(public_path('uploads/ads'), $imageName);
                            $data['ads_message'] = url('uploads/ads/' . $imageName);
                        }
                        elseif ($request->has('ads_message')) {
                            $data['ads_message'] = $request->ads_message;
                        }

                        if ($request->has('ads_show')) {
                            $data['ads_show'] = ($request->ads_show == 1 || $request->ads_show == true) ? 1 : 0;
                        }
                        if ($request->has('app_notif_show')) {
                            $data['app_notif_show'] = ($request->app_notif_show == 1 || $request->app_notif_show == true) ? 1 : 0;
                        }

                        if (!empty($data)) {
                            DB::table('settings')->update($data);
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'message'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function DeleteFeature(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        if (DB::table('feature')->where('id', $request->feature_id)->count() == 1) {
                            DB::table('feature')->where('id', $request->feature_id)->delete();
                            return response()->json([
                                'status' => 'success',
                                'message' => 'successful'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'invalid'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function AddFeature(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        $data = [
                            'title' => $request->title,
                            'description' => $request->description,
                            'image' => $request->image,
                            'link' => $request->link
                        ];
                        DB::table('feature')->insert($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'successful'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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

    public function DeleteApp(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        if (DB::table('app_download')->where('id', $request->feature_id)->count() == 1) {
                            DB::table('app_download')->where('id', $request->feature_id)->delete();
                            return response()->json([
                                'status' => 'success',
                                'message' => 'successful'
                            ]);
                        }
                        else {
                            return response()->json([
                                'status' => 403,
                                'message' => 'invalid'
                            ])->setStatusCode(403);
                        }
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function NewApp(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        $data = [
                            'app_name' => $request->app_name,
                            'app_version' => $request->app_version,
                            'app_link' => $request->app_link,
                            'platform' => $request->platform
                        ];
                        DB::table('app_download')->insert($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'successful'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function PaymentInfo(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    if ($user->status == 1 and $user->type == 'ADMIN') {
                        $data = [
                            'mon_app_key' => $request->mon_app_key,
                            'mon_sk_key' => $request->mon_sk_key,
                            'mon_con_num' => $request->mon_con_num,
                            'mon_bvn' => $request->mon_bvn,
                            'min' => $request->min,
                            'max' => $request->max,
                            'account_number' => $request->account_number,
                            'bank_name' => $request->bank_name,
                            'account_name' => $request->account_name,
                            'psk' => $request->psk,
                            'psk_bvn' => $request->psk_bvn,
                            'plive' => $request->plive
                        ];
                        if (DB::table('habukhan_key')->count() == 0) {
                            DB::table('habukhan_key')->insert($data);
                        }
                        else {
                            DB::table('habukhan_key')->update($data);
                        }
                        return response()->json([
                            'status' => 'success',
                            'message' => 'updated'
                        ]);
                    }
                    else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Reload the Browser'
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
    public function ResetPassword(Request $request)
    {
        // Check if request has valid device key OR is from allowed origin
        $explode_url = explode(',', config('app.habukhan_app_key'));
        $origin = $request->headers->get('origin');
        $authHeader = $request->header('Authorization');
        $deviceKey = config('app.habukhan_device_key');
        
        // Allow if: no origin header, OR origin in whitelist, OR device key matches
        $isAuthorized = empty($origin) || in_array($origin, $explode_url) || $authHeader === $deviceKey;
        
        if ($isAuthorized) {
            $user_d = DB::table('user')->where(['status' => 1, 'email' => $request->email]);
            if ($user_d->count() == 1) {
                $user = $user_d->first();
                $otp = mt_rand(100000, 999999);
                DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['otp' => $otp]);
                $email_data = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'title' => 'RESET PASSWORD',
                    'sender_mail' => $this->general()->app_email,
                    'user_email' => $user->email,
                    'app_name' => $this->general()->app_name,
                    'date' => $this->system_date(),
                    'otp' => $otp,
                    'reset_url' => config('app.app_url') . "/resetpassword/verify/adex/$otp/reset",
                    'app_phone' => $this->general()->app_phone
                ];
                MailController::send_mail($email_data, 'email.reset-password');
                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP Sent Successfully'
                ]);
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Invalid Email Address'
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
    public function ChangePPassword(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $otp_to_check = $request->otp ?? $request->id;
            $user_d = DB::table('user')->where(['status' => 1, 'otp' => $otp_to_check]);
            if ($user_d->count() == 1) {
                $user = $user_d->first();
                $main_validator = validator::make($request->all(), [
                    'password' => 'required|min:8',
                    'confirmpassword' => 'required|min:8',
                ]);
                if ($main_validator->fails()) {
                    return response()->json([
                        'message' => $main_validator->errors()->first(),
                        'status' => 403
                    ])->setStatusCode(403);
                }
                else {
                    $password = password_hash($request->password, PASSWORD_DEFAULT, array('cost' => 16));
                    DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['otp' => null, 'password' => $password]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'password reseted'
                    ]);
                }
            }
            else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Link Expired'
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
    public function InviteUser(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                if (DB::table('user')->where(['id' => $this->verifytoken($request->id)])->count() == 1) {
                    $user = DB::table('user')->where(['id' => $this->verifytoken($request->id)])->first();
                    $main_validator = validator::make($request->all(), [
                        'refemail' => "required|email",
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    }
                    else {
                        $email_data = [
                            'name' => $request->refemail,
                            'email' => $request->refemail,
                            'username' => $request->refemail,
                            'title' => 'Invitation',
                            'sender_mail' => $this->general()->app_email,
                            'user_email' => $user->email,
                            'app_name' => $this->general()->app_name,
                            'date' => $this->system_date(),
                            'invite_url' => config('app.app_url') . "/auth/register/$user->username",
                            'app_phone' => $this->general()->app_phone
                        ];
                        MailController::send_mail($email_data, 'email.invite');
                    }
                }
                else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Invalid Email Address'
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