<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CableSend extends Controller
{
    public static function Autopilot($data)
    {
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();

            $reference = (new Controller)->generateAutopilotReference();
            $payload = [
                'networkId' => $cable_id->autopilot_id,
                'planId' => $cable_plan->autopilot,
                'phone' => $sendRequest->iuc,
                'reference' => $reference,
                'paymentTypes' => 'FULL_PAYMENT'
            ];

            // Store reference immediately
            DB::table('cable')->where('transid', $data['transid'])->update(['api_reference' => $reference]);

            $response = (new Controller)->autopilot_request('/v1/cable', $payload);

            if (!empty($response)) {
                if (isset($response['status']) && $response['status'] == true) {
                    if (isset($response['data']['message'])) {
                        DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['data']['message']]);
                    }
                    return 'success';
                }
                else if (isset($response['status']) && $response['status'] == false) {
                    if (isset($response['data']['message'])) {
                        DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['data']['message']]);
                    }
                    return 'fail';
                }
                else {
                    return 'process';
                }
            }
            return 'process';
        }
        else {
            return 'fail';
        }
    }

    public static function Habukhan1($data)
    {
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'cable' => $cable_id->plan_id,
                'iuc' => $sendRequest->iuc,
                'cable_plan' => $cable_plan->habukhan1,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/cable/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'cable' => $cable_id->plan_id,
                'iuc' => $sendRequest->iuc,
                'cable_plan' => $cable_plan->habukhan2,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/cable/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'cable' => $cable_id->plan_id,
                'iuc' => $sendRequest->iuc,
                'cable_plan' => $cable_plan->habukhan3,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/cable/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'cable' => $cable_id->plan_id,
                'iuc' => $sendRequest->iuc,
                'cable_plan' => $cable_plan->habukhan4,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/cable/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where('plan_id', $data['plan_id'])->first();
            $cable_id = DB::table('cable_id')->where(['cable_name' => strtolower($sendRequest->cable_name)])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'cable' => $cable_id->plan_id,
                'iuc' => $sendRequest->iuc,
                'cable_plan' => $cable_plan->habukhan5,
                'bypass' => true,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/cable/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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

    public function Vtpass($data)
    {
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where(['plan_id' => $data['plan_id']])->first();
            $other_api = DB::table('other_api')->first();
            $system = DB::table('general')->first();
            if ($sendRequest->cable_name == 'STARTIME') {
                $cable_name = 'startimes';
            }
            else {
                $cable_name = strtolower($sendRequest->cable_name);
            }
            $paypload = array(
                'serviceID' => strtolower($cable_name),
                'billersCode' => $sendRequest->iuc,
                'variation_code' => $cable_plan->vtpass,
                'phone' => $system->app_phone,
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
                    if ($response['code'] == 000) {
                        $plan_status = 'success';
                    }
                    else if ($response['response_description'] == 'TRANSACTION SUCCESSFUL') {
                        $plan_status = 'success';
                    }
                    else {
                        $plan_status = 'process';
                    }
                }
                else {
                    $plan_status = null;
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

    public function Showmax($data)
    {
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $cable_plan = DB::table('cable_plan')->where(['plan_id' => $data['plan_id']])->first();
            $other_api = DB::table('other_api')->first();
            $system = DB::table('general')->first();

            $paypload = array(
                'serviceID' => 'showmax',
                'billersCode' => $sendRequest->iuc, // Phone number for Showmax
                'variation_code' => $cable_plan->vtpass,
                'phone' => $system->app_phone,
                'request_id' => Carbon::parse($this->system_date())->formatLocalized("%Y%m%d%H%M%S") . '_' . $data['transid']
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
                    if ($response['code'] == 000) {
                        $plan_status = 'success';
                    }
                    else if ($response['response_description'] == 'TRANSACTION SUCCESSFUL') {
                        $plan_status = 'success';
                    }
                    else {
                        $plan_status = 'process';
                    }
                }
                else {
                    $plan_status = null;
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
        if (DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('cable')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $message = strtoupper($sendRequest->username) . ' wants to buy ' . $sendRequest->cable_name . ' ' . $sendRequest->cable_plan . ' ₦' . number_format($sendRequest->amount, 2) . ' to ' . $sendRequest->iuc . '.  Refreence is ' . $sendRequest->transid;
            $datas = [
                'mes' => $message,
                'title' => 'CABLE PURCHASE'
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

}