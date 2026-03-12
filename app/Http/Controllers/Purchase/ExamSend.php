<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $exam_id = DB::table('exam_id')->where('exam_name', $sendRequest->exam_name)->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'exam' => $exam_id->plan_id,
                'quantity' => $sendRequest->quantity
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/exam/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = $response['pin'];
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin]);
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Habukhan2($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $exam_id = DB::table('exam_id')->where('exam_name', $sendRequest->exam_name)->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'exam' => $exam_id->plan_id,
                'quantity' => $sendRequest->quantity
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/exam/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = $response['pin'];
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin]);
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Habukhan3($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $exam_id = DB::table('exam_id')->where('exam_name', $sendRequest->exam_name)->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'exam' => $exam_id->plan_id,
                'quantity' => $sendRequest->quantity
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/exam/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = $response['pin'];
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin]);
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Habukhan4($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $exam_id = DB::table('exam_id')->where('exam_name', $sendRequest->exam_name)->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'exam' => $exam_id->plan_id,
                'quantity' => $sendRequest->quantity
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/exam/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = $response['pin'];
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin]);
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Habukhan5($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $exam_id = DB::table('exam_id')->where('exam_name', $sendRequest->exam_name)->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'exam' => $exam_id->plan_id,
                'quantity' => $sendRequest->quantity
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/exam/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $pin = $response['pin'];
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin]);
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    $plan_status = 'fail';
                } else if ($response['status'] == 'process') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'process';
                }
            } else {
                $plan_status = null;
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }


    public static function Easy($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $habukhan_api = DB::table('other_api')->first();

            $exam_board = 1; // Default WAEC
            if ($sendRequest->exam_name == 'NECO') {
                $exam_board = 2;
            } else if ($sendRequest->exam_name == 'NABTEB') {
                $exam_board = 3;
            } else if ($sendRequest->exam_name == 'NBAIS') {
                $exam_board = 4;
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://easyaccess.com.ng/api/live/v1/exam-pins",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode(array(
                    'exam_board' => $exam_board,
                    'no_of_pins' => $sendRequest->quantity,
                )),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $habukhan_api->easy_access,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ),
            ));
            $dataapi = curl_exec($curl);
            $response = json_decode($dataapi, true);
            curl_close($curl);

            // Log response for debugging
            $masked_token = substr($habukhan_api->easy_access, 0, 5) . "..." . substr($habukhan_api->easy_access, -5);
            \Log::info("EasyAccess Exam Pins Trace [$data[transid]]: ", [
                'token_used' => $masked_token,
                'response' => $response
            ]);

            if ($response) {
                $res = $response['res'] ?? $response;
                $status = strtolower($res['status'] ?? '');
                $code = $res['code'] ?? 0;

                if ($status == 'success' || $code == 200 || $code == 201) {
                    $pins = $res['pins'] ?? [];
                    if (is_array($pins)) {
                        $pin_string = implode(", ", $pins);
                    } else {
                        $pin_string = $pins;
                    }
                    DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['purchase_code' => $pin_string]);
                    return 'success';
                } else {
                    return 'fail';
                }
            }
            return 'fail';
        } else {
            return 'fail';
        }
    }
    public function Vtpass($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();
            $paypload = array(
                'serviceID' => 'waec',
                'phone' => $this->core()->app_phone,
                'variation_code' => 'waecdirect',
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
                        if ((isset($response['purchased_code'])) && !empty($response['purchased_code'])) {
                            DB::table('bill')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['token' => $response['purchased_code']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['response_description'] != 'TRANSACTION SUCCESSFUL') {
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else {
                    $plan_status = null;
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Self($data)
    {
        if (DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('exam')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            if (DB::table('stock_result_pin')->where(['exam_name' => $sendRequest->exam_name, 'plan_status' => 0])->take($sendRequest->quantity)->count() >= $sendRequest->quantity) {
                $habukhan_pin = DB::table('stock_result_pin')->where(['exam_name' => $sendRequest->exam_name, 'plan_status' => 0])->take($sendRequest->quantity)->get();
                $result_pin[] = null;
                foreach ($habukhan_pin as $boss) {
                    $pin = DB::table('stock_result_pin')->where(['id' => $boss->id])->first();

                    $result_pin[] = $pin->exam_pin . "<=>" . $pin->exam_serial;


                    DB::table('stock_result_pin')->where(['id' => $boss->id])->update(['plan_status' => 1, 'buyer_username' => $sendRequest->username, 'bought_date' => $sendRequest->plan_date]);
                }
                $my_pin = implode(' ', $result_pin);

                DB::table('exam')->where(['username' => $sendRequest->username, 'transid' => $sendRequest->transid])->update(['purchase_code' => $my_pin]);

                return 'success';
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }
}
