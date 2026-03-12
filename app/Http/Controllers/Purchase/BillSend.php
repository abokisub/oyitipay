<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where('plan_id', $data['plan_id'])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'disco' => $bill_plan->habukhan1,
                'meter_number' => $sendRequest->meter_number,
                'meter_type' => strtolower($sendRequest->meter_type),
                'amount' => $sendRequest->amount,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/bill/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $tokenToSave = null;
                    if (!empty($response['token']))
                        $tokenToSave = $response['token'];
                    elseif (!empty($response['purchased_code']))
                        $tokenToSave = $response['purchased_code'];
                    elseif (!empty($response['pin']))
                        $tokenToSave = $response['pin'];
                    elseif (!empty($response['Pin']))
                        $tokenToSave = $response['Pin'];
                    elseif (!empty($response['mainToken']))
                        $tokenToSave = $response['mainToken'];

                    if ($tokenToSave) {
                        DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $tokenToSave]);
                    }
                    $plan_status = 'success';
                }
                else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                }
                else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }
            return $plan_status;
        }
        else {
            return 'fail';
        }
    }
    public static function Habukhan2($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where('plan_id', $data['plan_id'])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'disco' => $bill_plan->habukhan2,
                'meter_number' => $sendRequest->meter_number,
                'meter_type' => strtolower($sendRequest->meter_type),
                'amount' => $sendRequest->amount,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/bill/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $tokenToSave = null;
                    if (!empty($response['token']))
                        $tokenToSave = $response['token'];
                    elseif (!empty($response['purchased_code']))
                        $tokenToSave = $response['purchased_code'];
                    elseif (!empty($response['pin']))
                        $tokenToSave = $response['pin'];
                    elseif (!empty($response['Pin']))
                        $tokenToSave = $response['Pin'];
                    elseif (!empty($response['mainToken']))
                        $tokenToSave = $response['mainToken'];

                    if ($tokenToSave) {
                        DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $tokenToSave]);
                    }
                    $plan_status = 'success';
                }
                else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                }
                else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }
            return $plan_status;
        }
        else {
            return 'fail';
        }
    }

    public static function Habukhan3($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where('plan_id', $data['plan_id'])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'disco' => $bill_plan->habukhan3,
                'meter_number' => $sendRequest->meter_number,
                'meter_type' => strtolower($sendRequest->meter_type),
                'amount' => $sendRequest->amount,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/bill/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $tokenToSave = null;
                    if (!empty($response['token']))
                        $tokenToSave = $response['token'];
                    elseif (!empty($response['purchased_code']))
                        $tokenToSave = $response['purchased_code'];
                    elseif (!empty($response['pin']))
                        $tokenToSave = $response['pin'];
                    elseif (!empty($response['Pin']))
                        $tokenToSave = $response['Pin'];
                    elseif (!empty($response['mainToken']))
                        $tokenToSave = $response['mainToken'];

                    if ($tokenToSave) {
                        DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $tokenToSave]);
                    }
                    $plan_status = 'success';
                }
                else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                }
                else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }
            return $plan_status;
        }
        else {
            return 'fail';
        }
    }
    public static function Habukhan4($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where('plan_id', $data['plan_id'])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'disco' => $bill_plan->habukhan4,
                'meter_number' => $sendRequest->meter_number,
                'meter_type' => strtolower($sendRequest->meter_type),
                'amount' => $sendRequest->amount,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/bill/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $tokenToSave = null;
                    if (!empty($response['token']))
                        $tokenToSave = $response['token'];
                    elseif (!empty($response['purchased_code']))
                        $tokenToSave = $response['purchased_code'];
                    elseif (!empty($response['pin']))
                        $tokenToSave = $response['pin'];
                    elseif (!empty($response['Pin']))
                        $tokenToSave = $response['Pin'];
                    elseif (!empty($response['mainToken']))
                        $tokenToSave = $response['mainToken'];

                    if ($tokenToSave) {
                        DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $tokenToSave]);
                    }
                    $plan_status = 'success';
                }
                else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                }
                else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }
            return $plan_status;
        }
        else {
            return 'fail';
        }
    }

    public static function Habukhan5($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where('plan_id', $data['plan_id'])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'disco' => $bill_plan->habukhan5,
                'meter_number' => $sendRequest->meter_number,
                'meter_type' => strtolower($sendRequest->meter_type),
                'amount' => $sendRequest->amount,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/bill/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $tokenToSave = null;
                    if (!empty($response['token']))
                        $tokenToSave = $response['token'];
                    elseif (!empty($response['purchased_code']))
                        $tokenToSave = $response['purchased_code'];
                    elseif (!empty($response['pin']))
                        $tokenToSave = $response['pin'];
                    elseif (!empty($response['Pin']))
                        $tokenToSave = $response['Pin'];
                    elseif (!empty($response['mainToken']))
                        $tokenToSave = $response['mainToken'];

                    if ($tokenToSave) {
                        DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $tokenToSave]);
                    }
                    $plan_status = 'success';
                }
                else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                }
                else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }
            return $plan_status;
        }
        else {
            return 'fail';
        }
    }

    public static function Email($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $message = strtoupper($sendRequest->username) . ' wants to buy ' . $sendRequest->disco_name . ' ' . $sendRequest->meter_type . ' ₦' . number_format($sendRequest->amount, 2) . ' to ' . $sendRequest->meter_number . '.  Refreence is ' . $sendRequest->transid;
            $datas = [
                'mes' => $message,
                'title' => 'BILL PURCHASE'
            ];
            $response = ApiSending::ADMINEMAIL($datas);

            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                }
                else if ($response['status'] != 'fail') {
                    $plan_status = 'fail';
                }
                else {
                    $plan_status = 'process';
                }
            }
            else {
                $plan_status = null;
            }

            return $plan_status;

        }
        else {
            return 'fail';
        }
    }

    public function Vtpass($data)
    {
        if (DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bill')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $bill_plan = DB::table('bill_plan')->where(['plan_id' => $data['plan_id']])->first();
            $other_api = DB::table('other_api')->first();
            $system = DB::table('general')->first();

            $paypload = array(
                'serviceID' => $bill_plan->vtpass,
                'billersCode' => $sendRequest->meter_number,
                'variation_code' => strtolower($sendRequest->meter_type),
                'phone' => $system->app_phone,
                'amount' => $sendRequest->amount,
                'request_id' => Carbon::now('Africa/Lagos')->format('YmdHi') . substr(md5($data['transid']), 0, 8)
            );
            $endpoints = "https://vtpass.com/api/pay";
            $headers = [
                "Authorization: Basic " . base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password),
                'Content-Type: application/json'
            ];
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['code'])) {
                    if ($response['code'] == '000') {
                        if ((isset($response['purchased_code'])) && !empty($response['purchased_code'])) {
                            DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $response['purchased_code']]);
                        }
                        $plan_status = 'success';
                    }
                    else if ($response['code'] == '099') {
                        $plan_status = 'process';
                    }
                    else {
                        $plan_status = 'fail';
                    }
                }
                else {
                    $plan_status = 'fail';
                }
            }
            else {
                $plan_status = null;
            }

            return $plan_status;
        }
        else {
            return 'fail';
        }
    }
}