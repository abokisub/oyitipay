<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NewStock extends Controller
{
    public function NewDataCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required|numeric',
                        'plan_day' => 'required',
                        'check_balance' => 'required',
                        'plan_type' => 'required',
                        'plan_size' => 'required',
                        'load_pin' => 'required',
                        'network' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric'
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
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        $check_plans = DB::table('data_card_plan');
                        if ($check_plans->count() > 0) {
                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                            $plan_id_get = $last_plan_id->plan_id;
                            $plan_id = $plan_id_get + 1;
                        } else {
                            $plan_id = 1;
                        }
                        $data = [
                            'network' => $request->network,
                            'load_pin' => $request->load_pin,
                            'plan_status' => $plan_status,
                            'smart' => $request->smart,
                            'agent' => $request->agent,
                            'awuf' => $request->awuf,
                            'special' => $request->special,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'name' => $request->name,
                            'plan_id' => $plan_id,
                            'plan_type' => $request->plan_type,
                            'plan_size' => $request->plan_size,
                            'plan_day' => $request->plan_day,
                            'check_balance' => $request->check_balance
                        ];
                        if (DB::table('data_card_plan')->where('plan_id', $plan_id)->count() == 0) {
                            if ($this->inserting_data('data_card_plan', $data)) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Data Card Plan Inserted'
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Try Again Later Or Contact A.D.E Developers'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'message' => 'Try Again Later Or Contact A.D.E Developers',
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function NewRechargeCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required',
                        'load_pin' => 'required',
                        'network' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric',
                        'check_balance' => 'required'
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
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        $check_plans = DB::table('recharge_card_plan');
                        if ($check_plans->count() > 0) {
                            $last_plan_id = $check_plans->orderBy('id', 'desc')->first();
                            $plan_id_get = $last_plan_id->plan_id;
                            $plan_id = $plan_id_get + 1;
                        } else {
                            $plan_id = 1;
                        }
                        $data = [
                            'network' => $request->network,
                            'load_pin' => $request->load_pin,
                            'plan_status' => $plan_status,
                            'smart' => $request->smart,
                            'agent' => $request->agent,
                            'awuf' => $request->awuf,
                            'special' => $request->special,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'name' => $request->name,
                            'plan_id' => $plan_id,
                            'check_balance' => $request->check_balance
                        ];
                        if (DB::table('recharge_card_plan')->where('plan_id', $plan_id)->count() == 0) {
                            if ($this->inserting_data('recharge_card_plan', $data)) {
                                return response()->json([
                                    'status' => 'success',
                                    'message' => 'Recharge Card Plan Inserted'
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'Try Again Later Or Contact A.D.E Developers'
                                ])->setStatusCode(403);
                            }
                        } else {
                            return response()->json([
                                'message' => 'Try Again Later Or Contact A.D.E Developers',
                                'status' => 403
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AllNewStock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $database_name = strtolower($request->database_name);
                    $search = strtolower($request->search);
                    if ($database_name == 'store_data_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'store_data_card' => DB::table('store_data_card')->where(function ($query) use ($search) {
                                        $query->orWhere('network', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('added_date', 'LIKE', "%$search%")->orWhere('serial', 'LIKE', "%$search%")->orWhere('buyer_username', 'LIKE', "%$search%")->orWhere('bought_date', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                                ]);
                            } else {
                                return response()->json([
                                    'store_data_card' => DB::table('store_data_card')->where(['plan_status' => $request->status])->where(function ($query) use ($search) {
                                        $query->orWhere('network', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('added_date', 'LIKE', "%$search%")->orWhere('serial', 'LIKE', "%$search%")->orWhere('buyer_username', 'LIKE', "%$search%")->orWhere('bought_date', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'store_data_card' => DB::table('store_data_card')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                                ]);
                            } else {
                                return response()->json([
                                    'store_data_card' => DB::table('store_data_card')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                                ]);
                            }
                        }
                    } else if ($database_name == 'store_recharge_card') {
                        if (!empty($search)) {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'store_recharge_card' => DB::table('store_recharge_card')->where(function ($query) use ($search) {
                                        $query->orWhere('network', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('added_date', 'LIKE', "%$search%")->orWhere('serial', 'LIKE', "%$search%")->orWhere('buyer_username', 'LIKE', "%$search%")->orWhere('bought_date', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                                ]);
                            } else {
                                return response()->json([
                                    'store_recharge_card' => DB::table('store_recharge_card')->where(['plan_status' => $request->status])->where(function ($query) use ($search) {
                                        $query->orWhere('network', 'LIKE', "%$search%")->orWhere('name', 'LIKE', "%$search%")->orWhere('pin', 'LIKE', "%$search%")->orWhere('added_date', 'LIKE', "%$search%")->orWhere('serial', 'LIKE', "%$search%")->orWhere('buyer_username', 'LIKE', "%$search%")->orWhere('bought_date', 'LIKE', "%$search%");
                                    })->orderBy('id', 'desc')->paginate($request->input('habukhan', 15))
                                ]);
                            }
                        } else {
                            if ($request->status == 'ALL') {
                                return response()->json([
                                    'store_recharge_card' => DB::table('store_recharge_card')->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                                ]);
                            } else {
                                return response()->json([
                                    'store_recharge_card' => DB::table('store_recharge_card')->where(['plan_status' => $request->status])->orderBy('id', 'desc')->paginate($request->input('habukhan', 15)),
                                ]);
                            }
                        }
                    } else {
                        return response()->json([
                            'data_card_plan' => DB::table('data_card_plan')->orderBy('id', 'desc')->get(),
                            'recharge_card_plan' => DB::table('recharge_card_plan')->orderBy('id', 'desc')->get(),
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }



    public function DeleteDataCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    foreach ($request->plan_id as $plan_id) {
                        DB::table('data_card_plan')->where('plan_id', $plan_id)->delete();
                    }
                    return response()->json([
                        'status' => 'success',
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DeleteRechargeCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {

                    foreach ($request->plan_id as $plan_id) {
                        DB::table('recharge_card_plan')->where('plan_id', $plan_id)->delete();
                    }

                    return response()->json([
                        'status' => 'success',
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RDataCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (DB::table('data_card_plan')->where(['plan_id' => $request->plan_id])->count() == 1) {

                        return response()->json([
                            'status' => 'success',
                            'plan' => DB::table('data_card_plan')->where('plan_id', $request->plan_id)->first()
                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'unable avialable'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RRechargeCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    if (DB::table('recharge_card_plan')->where(['plan_id' => $request->plan_id])->count() == 1) {
                        return response()->json([
                            'status' => 'success',
                            'plan' => DB::table('recharge_card_plan')->where('plan_id', $request->plan_id)->first()
                        ]);
                    } else {
                        return response()->json([
                            'status' => 403,
                            'message' => 'unable avialable'
                        ])->setStatusCode(403);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditDataCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required|numeric',
                        'plan_type' => 'required',
                        'plan_size' => 'required',
                        'load_pin' => 'required',
                        'network' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric',
                        'plan_day' => 'required',
                        'check_balance' => 'required'
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
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('data_card_plan')->where('plan_id', $request->plan_id)->count() != 1) {
                        return response()->json([
                            'message' => 'unable to edit',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }

                        $data = [
                            'network' => $request->network,
                            'load_pin' => $request->load_pin,
                            'plan_status' => $plan_status,
                            'smart' => $request->smart,
                            'agent' => $request->agent,
                            'awuf' => $request->awuf,
                            'special' => $request->special,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'name' => $request->name,
                            'plan_type' => $request->plan_type,
                            'plan_size' => $request->plan_size,
                            'plan_day' => $request->plan_day,
                            'check_balance' => $request->check_balance
                        ];

                        DB::table('data_card_plan')->where('plan_id', $request->plan_id)->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Data Card Plan Inserted'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditRechargeCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'name' => 'required',
                        'load_pin' => 'required',
                        'network' => 'required',
                        'smart' => 'required|numeric',
                        'awuf' => 'required|numeric',
                        'agent' => 'required|numeric',
                        'api' => 'required|numeric',
                        'special' => 'required|numeric',
                        'check_balance' => 'required',
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
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('recharge_card_plan')->where('plan_id', $request->plan_id)->count() != 1) {
                        return response()->json([
                            'message' => 'unable to edit',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }

                        $data = [
                            'network' => $request->network,
                            'load_pin' => $request->load_pin,
                            'plan_status' => $plan_status,
                            'smart' => $request->smart,
                            'agent' => $request->agent,
                            'awuf' => $request->awuf,
                            'special' => $request->special,
                            'api' => $request->api,
                            'habukhan1' => $request->habukhan1,
                            'habukhan2' => $request->habukhan2,
                            'habukhan3' => $request->habukhan3,
                            'habukhan4' => $request->habukhan4,
                            'habukhan5' => $request->habukhan5,
                            'free1' => $request->free1,
                            'free2' => $request->free2,
                            'free3' => $request->free3,
                            'name' => $request->name,
                            'check_balance' => $request->check_balance
                        ];

                        DB::table('recharge_card_plan')->where('plan_id', $request->plan_id)->update($data);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Recharge  Card Plan Edited'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function DeleteStockDataCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {

                    foreach ($request->plan_id as $plan_id) {
                        DB::table('store_data_card')->where('id', $plan_id)->delete();
                    }
                    return response()->json([
                        'status' => 'success',
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function DataCardPlansList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    return response()->json([
                        'plan' => DB::table('data_card_plan')->where(['network' => $request->network, 'plan_status' => 1])->orderBy('smart', 'asc')->get(),
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function StoreDataCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'pin' => 'required',
                        'data_card_id' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('data_card_plan')->where('plan_id', $request->data_card_id)->count() != 1) {
                        return response()->json([
                            'message' => 'Plan Type Not Found In Data Card Category',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        if ($request->generate_serial == true || $request->generate_serial == 1) {
                            $generate_s = 1;
                            $fake_serial = $request->pin;
                        } else {
                            $generate_s = 0;
                            $fake_serial = $request->serial;
                        }
                        if ((empty($request->serial)) && $generate_s == 0) {
                            return response()->json([
                                'message' => 'Serial Number Required',
                                'status' => 403
                            ])->setStatusCode(403);
                        } else {
                            $data_card_plan = DB::table('data_card_plan')->where('plan_id', $request->data_card_id)->first();

                            $pin = explode(',', $request->pin);
                            $serial = explode(',', $fake_serial);
                            for ($i = 0; $i < count($pin); $i++) {
                                $load_pin = $pin[$i];
                                $j = $i;
                                for ($a = 0; $a < count($serial); $a++) {
                                    if ($generate_s == 1) {
                                        $load_serial = rand(999999999, 100000000) . '_' . $a;
                                    } else {
                                        $load_serial = $serial[$a];
                                    }
                                    if ($j == $a) {
                                        if (DB::table('store_data_card')->where(['network' => $data_card_plan->network, 'serial' => $load_serial, 'pin' => $load_pin, 'data_card_id' => $data_card_plan->plan_id])->count() == 0) {
                                            $store_pin_now = [
                                                'name' => $data_card_plan->name . $data_card_plan->plan_size . '->' . $data_card_plan->plan_type . '->' . $data_card_plan->plan_day,
                                                'network' => $data_card_plan->network,
                                                'pin' => $load_pin,
                                                'serial' => $load_serial,
                                                'plan_status' => $plan_status,
                                                'buyer_username' => null,
                                                'added_date' => $this->system_date(),
                                                'bought_date' => null,
                                                'data_card_id' => $data_card_plan->plan_id
                                            ];

                                            $this->inserting_data('store_data_card', $store_pin_now);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function RStockDataCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    return response()->json([
                        'plan' => DB::table('store_data_card')->where('id', $request->plan_id)->first(),
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function EditDataCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'serial' => 'required',
                        'pin' => 'required',
                        'data_card_id' => 'required',
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('store_data_card')->where('id', $request->plan_id)->count() != 1) {
                        return response()->json([
                            'message' => 'unable to edit',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        $data_card_plan = DB::table('data_card_plan')->where('plan_id', $request->data_card_id)->first();
                        if (DB::table('store_data_card')->where(['network' => $data_card_plan->network, 'serial' => $request->serial, 'pin' => $request->pin, 'data_card_id' => $data_card_plan->plan_id])->where('id', '!=', $request->plan_id)->count() == 0) {
                            $data = [
                                'network' => $request->network,
                                'serial' => $request->serial,
                                'plan_status' => $plan_status,
                                'pin' => $request->pin,
                                'data_card_id' => $request->data_card_id,
                                'name' => $data_card_plan->name . $data_card_plan->plan_size . '->' . $data_card_plan->plan_type . '->' . $data_card_plan->plan_day,
                            ];

                            DB::table('store_data_card')->where('id', $request->plan_id)->update($data);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Store Data Card  Edited'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Already Exits In our Data Base'
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function DeleteStockRechargeCardPlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    foreach ($request->plan_id as $plan_id) {
                        DB::table('store_recharge_card')->where('id', $plan_id)->delete();
                    }
                    return response()->json([
                        'status' => 'success',
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function RStockRechargeCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    return response()->json([
                        'plan' => DB::table('store_recharge_card')->where('id', $request->plan_id)->first(),
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function RechargeCardPlanList(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    return response()->json([
                        'plan' => DB::table('recharge_card_plan')->where(['network' => $request->network, 'plan_status' => 1])->orderBy('id', 'desc')->get(),
                        'message' => 'success'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function AddStockRechargeCard(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'pin' => 'required',
                        'recharge_card_id' => 'required'
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('recharge_card_plan')->where('plan_id', $request->recharge_card_id)->count() != 1) {
                        return response()->json([
                            'message' => 'Plan Type Not Found In Data Card Category',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        if ($request->generate_serial == true || $request->generate_serial == 1) {
                            $generate_s = 1;
                            $fake_serial = $request->pin;
                        } else {
                            $generate_s = 0;
                            $fake_serial = $request->serial;
                        }
                        if ((empty($request->serial)) && $generate_s == 0) {
                            return response()->json([
                                'message' => 'Serial Number Required',
                                'status' => 403
                            ])->setStatusCode(403);
                        } else {
                            $data_card_plan = DB::table('recharge_card_plan')->where('plan_id', $request->recharge_card_id)->first();

                            $pin = explode(',', $request->pin);
                            $serial = explode(',', $fake_serial);
                            for ($i = 0; $i < count($pin); $i++) {
                                $load_pin = $pin[$i];
                                $j = $i;
                                for ($a = 0; $a < count($serial); $a++) {
                                    if ($generate_s == 1) {
                                        $load_serial = rand(999999999, 100000000) . '_' . $a;
                                    } else {
                                        $load_serial = $serial[$a];
                                    }
                                    if ($j == $a) {
                                        if (DB::table('store_recharge_card')->where(['network' => $data_card_plan->network, 'serial' => $load_serial, 'pin' => $load_pin, 'recharge_card_id' => $data_card_plan->plan_id])->count() == 0) {
                                            $store_pin_now = [
                                                'name' => $data_card_plan->name,
                                                'network' => $data_card_plan->network,
                                                'pin' => $load_pin,
                                                'serial' => $load_serial,
                                                'plan_status' => $plan_status,
                                                'buyer_username' => null,
                                                'added_date' => $this->system_date(),
                                                'bought_date' => null,
                                                'recharge_card_id' => $data_card_plan->plan_id
                                            ];

                                            $this->inserting_data('store_recharge_card', $store_pin_now);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function EditStoreRechargePlan(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() == 1) {
                    $main_validator = validator::make($request->all(), [
                        'network' => 'required',
                        'serial' => 'required',
                        'pin' => 'required',
                        'recharge_card_id' => 'required',
                        'plan_id' => 'required',
                    ]);
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else if (DB::table('store_recharge_card')->where('id', $request->plan_id)->count() != 1) {
                        return response()->json([
                            'message' => 'unable to edit',
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        // plan status
                        if ($request->plan_status == true || $request->plan_status == 1) {
                            $plan_status = 1;
                        } else {
                            $plan_status = 0;
                        }
                        $data_card_plan = DB::table('recharge_card_plan')->where('plan_id', $request->recharge_card_id)->first();
                        if (DB::table('store_recharge_card')->where(['network' => $data_card_plan->network, 'serial' => $request->serial, 'pin' => $request->pin, 'recharge_card_id' => $data_card_plan->plan_id])->where('id', '!=', $request->plan_id)->count() == 0) {
                            $data = [
                                'network' => $request->network,
                                'serial' => $request->serial,
                                'plan_status' => $plan_status,
                                'pin' => $request->pin,
                                'recharge_card_id' => $request->recharge_card_id,
                                'name' => $data_card_plan->name
                            ];

                            DB::table('store_recharge_card')->where('id', $request->plan_id)->update($data);
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Store Recharge Card  Edited'
                            ]);
                        } else {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Already Exits In our Data Base'
                            ])->setStatusCode(403);
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function DataCardLock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // data sme
                    if ($request->mtn == true || $request->mtn == 1) {
                        $mtn = 1;
                    } else {
                        $mtn = 0;
                    }
                    if ($request->glo == true || $request->glo == 1) {
                        $glo = 1;
                    } else {
                        $glo = 0;
                    }
                    if ($request->airtel == true || $request->airtel == 1) {
                        $airtel = 1;
                    } else {
                        $airtel = 0;
                    }
                    if ($request->mobile == true || $request->mobile == 1) {
                        $mobile = 1;
                    } else {
                        $mobile = 0;
                    }

                    $mtn_data = [
                        'data_card' => $mtn,
                    ];
                    $glo_data = [
                        'data_card' => $glo,
                    ];
                    $airtel_data = [
                        'data_card' => $airtel,
                    ];
                    $mobile_data = [
                        'data_card' => $mobile,
                    ];
                    $this->updateData($mtn_data, 'network', ['network' => 'MTN']);
                    $this->updateData($glo_data, 'network', ['network' => 'GLO']);
                    $this->updateData($mobile_data, 'network', ['network' => '9MOBILE']);
                    $this->updateData($airtel_data, 'network', ['network' => 'AIRTEL']);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function RechargeCardLock(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));
        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            if (!empty($request->id)) {
                $check_user = DB::table('user')->where(['status' => 1, 'id' => $this->verifytoken($request->id)])->where(function ($query) {
                    $query->where('type', 'ADMIN');
                });
                if ($check_user->count() > 0) {
                    // data sme
                    if ($request->mtn == true || $request->mtn == 1) {
                        $mtn = 1;
                    } else {
                        $mtn = 0;
                    }
                    if ($request->glo == true || $request->glo == 1) {
                        $glo = 1;
                    } else {
                        $glo = 0;
                    }
                    if ($request->airtel == true || $request->airtel == 1) {
                        $airtel = 1;
                    } else {
                        $airtel = 0;
                    }
                    if ($request->mobile == true || $request->mobile == 1) {
                        $mobile = 1;
                    } else {
                        $mobile = 0;
                    }

                    $mtn_data = [
                        'recharge_card' => $mtn,
                    ];
                    $glo_data = [
                        'recharge_card' => $glo,
                    ];
                    $airtel_data = [
                        'recharge_card' => $airtel,
                    ];
                    $mobile_data = [
                        'recharge_card' => $mobile,
                    ];
                    $this->updateData($mtn_data, 'network', ['network' => 'MTN']);
                    $this->updateData($glo_data, 'network', ['network' => 'GLO']);
                    $this->updateData($mobile_data, 'network', ['network' => '9MOBILE']);
                    $this->updateData($airtel_data, 'network', ['network' => 'AIRTEL']);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Updated'
                    ]);
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'User Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return response()->json([
                    'status' => 403,
                    'message' => 'Not Authorised'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
    public function UserDataCardPlan(Request $request)
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
                    ]);
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    } else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    } else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    } else if ($adex->type == 'API') {
                        $user_type = 'api';
                    } else {
                        $user_type = 'special';
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $get_network = DB::table('network')->where('plan_id', $request->network)->first();
                        $all_plan = DB::table('data_card_plan')->where(['network' => $get_network->network, 'plan_status' => 1]);
                        if ($all_plan->count() > 0) {
                            foreach ($all_plan->get() as $adex => $plan) {
                                $data_plan[] = ['name' => $plan->name . $plan->plan_size . ' ' . $plan->plan_type . ' = ₦' . number_format($plan->$user_type, 2) . ' ' . $plan->plan_day, 'plan_id' => $plan->plan_id, 'amount' => $plan->$user_type];
                                ;
                            }
                        } else {
                            $data_plan = [];
                        }
                        return response()->json([
                            'status' => 'success',
                            'plan' => $data_plan
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }

    public function UserRechargeCardPlan(Request $request)
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
                    ]);
                    // validate user type
                    if ($adex->type == 'SMART') {
                        $user_type = 'smart';
                    } else if ($adex->type == 'AGENT') {
                        $user_type = 'agent';
                    } else if ($adex->type == 'AWUF') {
                        $user_type = 'awuf';
                    } else if ($adex->type == 'API') {
                        $user_type = 'api';
                    } else {
                        $user_type = 'special';
                    }
                    if ($main_validator->fails()) {
                        return response()->json([
                            'message' => $main_validator->errors()->first(),
                            'status' => 403
                        ])->setStatusCode(403);
                    } else {
                        $get_network = DB::table('network')->where('plan_id', $request->network)->first();
                        $all_plan = DB::table('recharge_card_plan')->where(['network' => $get_network->network, 'plan_status' => 1]);
                        if ($all_plan->count() > 0) {
                            foreach ($all_plan->get() as $adex => $plan) {
                                $data_plan[] = ['name' => $plan->name . ' = ₦' . number_format($plan->$user_type, 2), 'plan_id' => $plan->plan_id, 'amount' => $plan->$user_type];
                                ;
                            }
                        } else {
                            $data_plan = [];
                        }
                        return response()->json([
                            'status' => 'success',
                            'plan' => $data_plan
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 403,
                        'message' => 'Not Authorised'
                    ])->setStatusCode(403);
                }
            } else {
                return redirect(config('app.error_500'));
                return response()->json([
                    'status' => 403,
                    'message' => 'Unable to Authenticate System'
                ])->setStatusCode(403);
            }
        } else {
            return redirect(config('app.error_500'));
            return response()->json([
                'status' => 403,
                'message' => 'Unable to Authenticate System'
            ])->setStatusCode(403);
        }
    }
}
