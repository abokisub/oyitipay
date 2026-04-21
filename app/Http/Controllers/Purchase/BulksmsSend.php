<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BulksmsSend extends Controller
{
    public static function Habukhan1($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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

        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {

                if ($response['status'] == 'success') {
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
    public static function Hollatag($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $habukhan_api = DB::table('other_api')->first();

            // Convert phone numbers to international format (234xxx) required by Hollatag
            $numbers = explode(',', $sendRequest->correct_number);
            $formatted_numbers = [];
            foreach ($numbers as $number) {
                $number = trim($number);
                // Remove any spaces or special characters
                $number = preg_replace('/[^0-9]/', '', $number);
                
                // Convert to international format
                if (substr($number, 0, 1) == '0') {
                    // Nigerian local format (0803xxx) -> 234803xxx
                    $number = '234' . substr($number, 1);
                } elseif (substr($number, 0, 3) != '234') {
                    // If not starting with 234, assume it's local without 0
                    $number = '234' . $number;
                }
                $formatted_numbers[] = $number;
            }
            $to_numbers = implode(',', $formatted_numbers);

            $postData = [
                "user" => $habukhan_api->hollatag_username,
                "pass" => $habukhan_api->hollatag_password,
                "from" => $sendRequest->sender_name,
                "to" => $to_numbers,
                "msg" => $sendRequest->message,
                "type" => "0",
            ];

            \Log::info('Hollatag BulkSMS Request', ['data' => $postData, 'transid' => $data['transid']]);

            $url = 'https://sms.hollatags.com/api/send';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response_sms = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            \Log::info('Hollatag BulkSMS Response', [
                'response' => $response_sms, 
                'curl_error' => $curl_error, 
                'http_code' => $http_code,
                'transid' => $data['transid']
            ]);
            
            if (!empty($response_sms)) {
                if (strpos($response_sms, 'sent') !== false) {
                    return 'success';
                } elseif (strpos($response_sms, 'error_user') !== false) {
                    \Log::error('Hollatag: error_user - Account may be suspended or IP blocked');
                    return 'fail';
                } elseif (strpos($response_sms, 'error_balance') !== false) {
                    \Log::error('Hollatag: Insufficient balance');
                    return 'fail';
                } else {
                    \Log::error('Hollatag: Unknown error - ' . $response_sms);
                    return 'fail';
                }
            } else {
                return 'process';
            }
        } else {
            \Log::error('Hollatag BulkSMS: Record not found', ['username' => $data['username'], 'transid' => $data['transid']]);
            return 'fail';
        }
    }

    public static function Adex1($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex1_username . ":" . $adex_api->adex1_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->adex_website1,
                'endpoint' => $api_website->adex_website1 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
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

    public static function Adex2($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex2_username . ":" . $adex_api->adex2_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->adex_website2,
                'endpoint' => $api_website->adex_website2 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
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

    public static function Adex3($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex3_username . ":" . $adex_api->adex3_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->adex_website3,
                'endpoint' => $api_website->adex_website3 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
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

    public static function Adex4($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex4_username . ":" . $adex_api->adex4_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->adex_website4,
                'endpoint' => $api_website->adex_website4 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
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

    public static function Adex5($data)
    {
        if (DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('bulksms')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex5_username . ":" . $adex_api->adex5_password);
            $paypload = array(
                'sender' => $sendRequest->sender_name,
                'number' => $sendRequest->correct_number,
                'message' => $sendRequest->message,
            );
            $admin_details = [
                'website_url' => $api_website->adex_website5,
                'endpoint' => $api_website->adex_website5 . "/api/bulksms/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
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

}
