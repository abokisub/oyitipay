<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataSend extends Controller
{
    public static function Autopilot($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();

            // ✅ PROFESSIONAL FIX: Skip Autopilot if plan ID is missing (instant failover)
            if (empty($dataplan->autopilot)) {
                return 'fail';
            }

            // Map plan types to Autopilot naming
            $type_map = [
                'GIFTING' => 'DIRECT GIFTING',
                'COOPERATE GIFTING' => 'CORPORATE GIFTING',
                'SME' => 'SME',
                'SME 2' => 'SME',
                'DATASHARE' => 'DIRECT GIFTING'
            ];
            $dataType = $type_map[$dataplan->plan_type] ?? $dataplan->plan_type;

            $reference = (new Controller)->generateAutopilotReference();
            $payload = [
                'networkId' => (string) $network->autopilot_id,
                'dataType' => $dataType,
                'planId' => $dataplan->autopilot,
                'phone' => $sendRequest->plan_phone,
                'reference' => $reference
            ];

            // Store reference immediately
            DB::table('data')->where('transid', $data['transid'])->update(['api_reference' => $reference]);

            \Log::info('Autopilot Data REQUEST:', ['payload' => $payload, 'transid' => $data['transid']]);

            $response = (new Controller)->autopilot_request('/v1/data', $payload);

            \Log::info('Autopilot Data RESPONSE:', ['response' => $response, 'transid' => $data['transid']]);

            if (!empty($response)) {
                // Autopilot uses both 'status' and 'code' fields
                // Success: status=true AND code=200
                // Failed: status=false OR code=424
                $status = $response['status'] ?? false;
                $code = $response['code'] ?? 0;

                if ($status == true && $code == 200) {
                    \Log::info('Autopilot Data: Returning SUCCESS', ['transid' => $data['transid']]);
                    return 'success';
                } else if ($status == false || $code == 424) {
                    \Log::info('Autopilot Data: Returning FAIL', ['transid' => $data['transid'], 'code' => $code, 'message' => $response['data']['message'] ?? 'No message']);
                    return 'fail';
                } else {
                    \Log::info('Autopilot Data: Returning PROCESS (code=' . $code . ')', ['transid' => $data['transid'], 'response' => $response]);
                    return 'process';
                }
            }
            return 'process';
        } else {
            return 'fail';
        }
    }

    public static function Habukhan1($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'data_plan' => $dataplan->habukhan1,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'data_plan' => $dataplan->habukhan2,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'data_plan' => $dataplan->habukhan3,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'data_plan' => $dataplan->habukhan4,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'data_plan' => $dataplan->habukhan5,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::HabukhanApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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

    public static function Adex1($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex1_username . ":" . $adex_api->adex1_password);
            
            // Format phone number for Adex API (they expect 11 digits Nigerian format)
            $phone = $sendRequest->plan_phone;
            \Log::info('Adex1 Data Phone Before Format:', ['original_phone' => $phone]);
            
            // Ensure it's 11 digits starting with 0
            if (substr($phone, 0, 3) == '234') {
                $phone = '0' . substr($phone, 3); // Convert 2347040540018 to 07040540018
            }
            // If it doesn't start with 0, add it
            if (substr($phone, 0, 1) != '0' && strlen($phone) == 10) {
                $phone = '0' . $phone; // Convert 7040540018 to 07040540018
            }
            
            \Log::info('Adex1 Data Phone After Format:', ['formatted_phone' => $phone]);
            
            // Use proper Adex network ID (1 for MTN according to Adex docs)
            $adex_network_id = 1; // Default to MTN
            if (strtoupper($sendRequest->network) == 'AIRTEL') {
                $adex_network_id = 2;
            } elseif (strtoupper($sendRequest->network) == 'GLO') {
                $adex_network_id = 3;
            } elseif (strtoupper($sendRequest->network) == '9MOBILE') {
                $adex_network_id = 4;
            }
            
            $paypload = array(
                'network' => $adex_network_id,
                'phone' => $phone,
                'data_plan' => $dataplan->adex1,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->adex_website1,
                'endpoint' => $api_website->adex_website1 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex2_username . ":" . $adex_api->adex2_password);
            
            // Format phone number for Adex API
            $phone = $sendRequest->plan_phone;
            if (substr($phone, 0, 3) == '234') {
                $phone = '0' . substr($phone, 3);
            }
            if (substr($phone, 0, 1) != '0' && strlen($phone) == 10) {
                $phone = '0' . $phone;
            }
            
            // Use proper Adex network ID
            $adex_network_id = 1; // Default to MTN
            if (strtoupper($sendRequest->network) == 'AIRTEL') {
                $adex_network_id = 2;
            } elseif (strtoupper($sendRequest->network) == 'GLO') {
                $adex_network_id = 3;
            } elseif (strtoupper($sendRequest->network) == '9MOBILE') {
                $adex_network_id = 4;
            }
            
            $paypload = array(
                'network' => $adex_network_id,
                'phone' => $phone,
                'data_plan' => $dataplan->adex2,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->adex_website2,
                'endpoint' => $api_website->adex_website2 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex3_username . ":" . $adex_api->adex3_password);
            
            // Format phone number for Adex API
            $phone = $sendRequest->plan_phone;
            if (substr($phone, 0, 3) == '234') {
                $phone = '0' . substr($phone, 3);
            }
            if (substr($phone, 0, 1) != '0' && strlen($phone) == 10) {
                $phone = '0' . $phone;
            }
            
            // Use proper Adex network ID
            $adex_network_id = 1; // Default to MTN
            if (strtoupper($sendRequest->network) == 'AIRTEL') {
                $adex_network_id = 2;
            } elseif (strtoupper($sendRequest->network) == 'GLO') {
                $adex_network_id = 3;
            } elseif (strtoupper($sendRequest->network) == '9MOBILE') {
                $adex_network_id = 4;
            }
            
            $paypload = array(
                'network' => $adex_network_id,
                'phone' => $phone,
                'data_plan' => $dataplan->adex3,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->adex_website3,
                'endpoint' => $api_website->adex_website3 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex4_username . ":" . $adex_api->adex4_password);
            
            // Format phone number for Adex API
            $phone = $sendRequest->plan_phone;
            if (substr($phone, 0, 3) == '234') {
                $phone = '0' . substr($phone, 3);
            }
            if (substr($phone, 0, 1) != '0' && strlen($phone) == 10) {
                $phone = '0' . $phone;
            }
            
            // Use proper Adex network ID
            $adex_network_id = 1; // Default to MTN
            if (strtoupper($sendRequest->network) == 'AIRTEL') {
                $adex_network_id = 2;
            } elseif (strtoupper($sendRequest->network) == 'GLO') {
                $adex_network_id = 3;
            } elseif (strtoupper($sendRequest->network) == '9MOBILE') {
                $adex_network_id = 4;
            }
            
            $paypload = array(
                'network' => $adex_network_id,
                'phone' => $phone,
                'data_plan' => $dataplan->adex4,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->adex_website4,
                'endpoint' => $api_website->adex_website4 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $adex_api = DB::table('adex_api')->first();
            $accessToken = base64_encode($adex_api->adex5_username . ":" . $adex_api->adex5_password);
            
            // Format phone number for Adex API
            $phone = $sendRequest->plan_phone;
            if (substr($phone, 0, 3) == '234') {
                $phone = '0' . substr($phone, 3);
            }
            if (substr($phone, 0, 1) != '0' && strlen($phone) == 10) {
                $phone = '0' . $phone;
            }
            
            // Use proper Adex network ID
            $adex_network_id = 1; // Default to MTN
            if (strtoupper($sendRequest->network) == 'AIRTEL') {
                $adex_network_id = 2;
            } elseif (strtoupper($sendRequest->network) == 'GLO') {
                $adex_network_id = 3;
            } elseif (strtoupper($sendRequest->network) == '9MOBILE') {
                $adex_network_id = 4;
            }
            
            $paypload = array(
                'network' => $adex_network_id,
                'phone' => $phone,
                'data_plan' => $dataplan->adex5,
                'bypass' => true,
                'request-id' => $data['transid']
            );
            $admin_details = [
                'website_url' => $api_website->adex_website5,
                'endpoint' => $api_website->adex_website5 . "/api/data/",
                'accessToken' => $accessToken
            ];
            $response = ApiSending::AdexApi($admin_details, $paypload);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
                    $plan_status = 'success';
                } else if ($response['status'] == 'fail') {
                    if (isset($response['response'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['response']]);
                    }
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

    public static function Msorg1($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->msorg1,
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website1 . "/api/data/",
                'token' => $msorg_api->msorg1
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'success';
                    } else {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'fail';
                    }
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Msorg2($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->msorg2,
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website2 . "/api/data/",
                'token' => $msorg_api->msorg2
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'success';
                    } else {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'fail';
                    }
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Msorg3($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->msorg3,
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website3 . "/api/data/",
                'token' => $msorg_api->msorg3
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'success';
                    } else {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'fail';
                    }
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Msorg4($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->msorg4,
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website4 . "/api/data/",
                'token' => $msorg_api->msorg4
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'success';
                    } else {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'fail';
                    }
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Msorg5($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->msorg5,
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website5 . "/api/data/",
                'token' => $msorg_api->msorg5
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'success';
                    } else {
                        if (isset($response['api_response'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['api_response']]);
                        }
                        $plan_status = 'fail';
                    }
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = null;
            }

            return $plan_status;
        } else {
            return 'fail';
        }
    }


    public static function Virus1($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();
            if ($sendRequest->network == 'MTN' and $sendRequest->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($sendRequest->network == 'AIRTEL' and $sendRequest->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }

            $paypload = array(
                'network' => $virus_id,
                'mobile' => $sendRequest->plan_phone,
                'plan_code' => $dataplan->virus1,
                'token' => $virus_api->virus1,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website1 . "/api/data",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);

            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else if (isset($response['code'])) {
                    if ($response['code'] == 'fail') {
                        $plan_status = "fail";
                    } else {
                        $plan_status = null;
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
    public static function Virus2($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();
            if ($sendRequest->network == 'MTN' and $sendRequest->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($sendRequest->network == 'AIRTEL' and $sendRequest->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }

            $paypload = array(
                'network' => $virus_id,
                'mobile' => $sendRequest->plan_phone,
                'plan_code' => $dataplan->virus2,
                'token' => $virus_api->virus2,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website2 . "/api/data",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else if (isset($response['code'])) {
                    if ($response['code'] == 'fail') {
                        $plan_status = "fail";
                    } else {
                        $plan_status = null;
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
    public static function Virus3($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();
            if ($sendRequest->network == 'MTN' and $sendRequest->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($sendRequest->network == 'AIRTEL' and $sendRequest->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }

            $paypload = array(
                'network' => $virus_id,
                'mobile' => $sendRequest->plan_phone,
                'plan_code' => $dataplan->virus3,
                'token' => $virus_api->virus3,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website3 . "/api/data",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else if (isset($response['code'])) {
                    if ($response['code'] == 'fail') {
                        $plan_status = "fail";
                    } else {
                        $plan_status = null;
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
    public static function Virus4($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();
            if ($sendRequest->network == 'MTN' and $sendRequest->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($sendRequest->network == 'AIRTEL' and $sendRequest->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }

            $paypload = array(
                'network' => $virus_id,
                'mobile' => $sendRequest->plan_phone,
                'plan_code' => $dataplan->virus4,
                'token' => $virus_api->virus4,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website4 . "/api/data",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else if (isset($response['code'])) {
                    if ($response['code'] == 'fail') {
                        $plan_status = "fail";
                    } else {
                        $plan_status = null;
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
    public static function Virus5($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();
            if ($sendRequest->network == 'MTN' and $sendRequest->network_type == 'GIFTING') {
                $virus_id = "gifting";
            } else if ($sendRequest->network == 'AIRTEL' and $sendRequest->network_type == 'COOPERATE GIFTING') {
                $virus_id = "airtel-cg";
            } else {
                $virus_id = $network->virus_id;
            }

            $paypload = array(
                'network' => $virus_id,
                'mobile' => $sendRequest->plan_phone,
                'plan_code' => $dataplan->virus5,
                'token' => $virus_api->virus5,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website5 . "/api/data",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);

            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
                        if (isset($response['desc'])) {
                            DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                        }
                        $plan_status = 'fail';
                    } else {
                        $plan_status = 'process';
                    }
                } else if (isset($response['code'])) {
                    if ($response['code'] == 'fail') {
                        $plan_status = "fail";
                    } else {
                        $plan_status = null;
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


    public static function Simhosting($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();


            $paypload = array(
                'network' => 1,
                'number' => $sendRequest->plan_phone,
                'plan' => $dataplan->simhosting,
                'senderID' => $data['transid']
            );
            $endpoints = "https://api.mysimhosting.com/v1/data";
            $headers = [
                "Authorization: Bearer " . $other_api->simhosting,
                'Content-Type: application/json'
            ];
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
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
    public static function Smeplug($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $other_api = DB::table('other_api')->first();

            if ($network->network == 'MTN') {
                $the_network = '1';
            } else if ($network->network == 'AIRTEL') {
                $the_network = '2';
            } else if ($network->network == 'GLO') {
                $the_network = '4';
            } else {
                $the_network = '3';
            }

            $paypload = array(
                'network_id' => $the_network,
                'phone' => $sendRequest->plan_phone,
                'plan_id' => $dataplan->smeplug,
            );
            $endpoints = "https://smeplug.ng/api/v1/data/purchase";
            $headers = [
                "Authorization: Bearer " . $other_api->smeplug,
                'Content-Type: application/json'
            ];
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == true) {
                        $plan_status = 'success';
                    } else if ($response['status'] == false) {
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
    public static function Msplug($data)
    {
        return null;
    }
    public static function Simserver($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();

            $paypload = array(
                'process' => "buy",
                'recipient' => $sendRequest->plan_phone,
                'api_key' => $other_api->simserver,
                'product_code' => $dataplan->simserver,
                'callback' => null,
                'user_reference' => $data['transid'],
            );
            $endpoints = "https://api.simservers.io";

            $response = ApiSending::OTHERAPI($endpoints, $paypload, null);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == 'success') {
                        $plan_status = 'success';
                    } else if ($response['status'] == 'fail') {
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
    public static function Ogdamns($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $other_api = DB::table('other_api')->first();

            if ($network->network == 'MTN') {
                $the_network = '1';
            } else if ($network->network == 'AIRTEL') {
                $the_network = '2';
            } else if ($network->network == 'GLO') {
                $the_network = '3';
            } else {
                $the_network = '4';
            }

            $paypload = array(
                'networkId' => $the_network,
                'phoneNumber' => $sendRequest->plan_phone,
                'planId' => $dataplan->ogdamns,
                'reference' => $data['transid']
            );
            $endpoints = "https://simhosting.ogdams.ng/api/v1/vend/data";
            $headers = [
                "Authorization: Bearer " . $other_api->ogdamns,
                'Content-Type: application/json'
            ];
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['status'])) {
                    if ($response['status'] == true) {
                        $plan_status = 'success';
                    } else if ($response['status'] == false) {
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
    public static function Email($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();

            $message = strtoupper($sendRequest->username) . ' wants to buy ' . $network->network . ' ' . $sendRequest->network_type . ' ' . $sendRequest->plan_name . ' ₦' . number_format($sendRequest->amount, 2) . ' to ' . $sendRequest->plan_phone . '.  Refreence is ' . $sendRequest->transid;
            $datas = [
                'mes' => $message,
                'title' => 'DATA PURCHASE'
            ];
            $response = ApiSending::ADMINEMAIL($datas);
            if (!empty($response)) {
                if ($response['status'] == 'success') {
                    $plan_status = 'success';
                } else if ($response['status'] != 'fail') {
                    $plan_status = 'fail';
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
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $other_api = DB::table('other_api')->first();

            if ($network->network == 'MTN') {
                $the_network = 1;
            } else if ($network->network == 'GLO') {
                $the_network = 2;
            } else if ($network->network == 'AIRTEL') {
                $the_network = 3;
            } else {
                $the_network = 4;
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://easyaccess.com.ng/api/live/v1/purchase-data",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode(array(
                    'network' => $the_network,
                    'mobileno' => $sendRequest->plan_phone,
                    'dataplan' => $dataplan->easyaccess,
                    'client_reference' => $data['transid'],
                )),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $other_api->easy_access,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ),
            ));
            $dataapi = curl_exec($curl);
            $response = json_decode($dataapi, true);
            curl_close($curl);

            // Log response and masked token for debugging
            $masked_token = substr($other_api->easy_access, 0, 5) . "..." . substr($other_api->easy_access, -5);
            \Log::info("EasyAccess Data Trace [$data[transid]]: ", [
                'token_used' => $masked_token,
                'response' => $response
            ]);

            if ($response) {
                // The new API returns results inside a 'res' object
                $res = $response['res'] ?? $response;
                $status = strtolower($res['status'] ?? '');
                $code = $res['code'] ?? 0;

                if ($status == 'success' || $status == 'successful' || $code == 200 || $code == 201) {
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

    public static function Megasubcloud($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();
            $payload = [
                'network_api_id' => '5',
                'mobile_number' => $sendRequest->plan_phone,
                'data_api_id' => $dataplan->megasubcloud,
                'validatephonenetwork' => false,
                'duplication_check' => false
            ];
            $endpoints = 'https://www.101terabyte.com/API/?action=buy_data';
            $headers = [
                "Authorization: Token ",
                'Content-Type: multipart/form-data',
                'Password: '
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoints);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (isset($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            $dataapi = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $response = json_decode($dataapi, true);
            if (!empty($response)) {
                if ($response['Detail']['success']) {
                    if ($response['Detail']['success'] == 'true') {
                        if (isset($response['Detail']['info']['Id'])) {
                            $trans = $response['Detail']['info']['Id'];
                            $realresponse = $response['Detail']['info']['realresponse'];
                            if ($response['Detail']['info']['Success'] == '1') {
                                DB::table('data')->where(['transid' => $sendRequest->transid])->update(['mega_trans' => $trans, 'api_response' => $realresponse]);
                                DB::table('message')->where(['transid' => $sendRequest->transid])->update(['message' => $realresponse]);
                                return 'success';
                            } else {
                                DB::table('data')->where(['transid' => $sendRequest->transid])->update(['mega_trans' => $trans, 'api_response' => $realresponse]);
                                DB::table('message')->where(['transid' => $sendRequest->transid])->update(['message' => $realresponse]);
                                return 'fail';
                            }
                        } else {
                            return 'fail';
                        }
                    } else {
                        return 'fail';
                    }
                } else {
                    return ' fail';
                }
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    public static function Mega($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();
            $payload = [
                'network_api_id' => '5',
                'mobile_number' => $sendRequest->plan_phone,
                'data_api_id' => $dataplan->megasub,
                'validatephonenetwork' => false,
                'duplication_check' => false
            ];
            $headers = [
                "Authorization: Token ",
                'Content-Type: multipart/form-data',
                'Password: '
            ];
            $endpoints = 'https://megasubplug.com/API/?action=buy_data';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoints);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (isset($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            $dataapi = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $api_result = json_decode($dataapi, true);
            if (!empty($api_result)) {
                if ($api_result['Detail']['success']) {
                    if ($api_result['Detail']['success'] == 'true') {
                        if (isset($api_result['Detail']['info']['Id'])) {
                            $trans = $api_result['Detail']['info']['Id'];
                            DB::table('data')->where(['transid' => $sendRequest->transid])->update(['mega_trans' => $trans]);
                            return 'success';
                        } else {
                            return 'fail';
                        }
                    } else {
                        return 'fail';
                    }
                } else {
                    return 'fail';
                }
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    public static function Vtpass($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();
            $paypload = array(
                'serviceID' => strtolower($network->virus_id),
                'billersCode' => $sendRequest->plan_phone,
                'variation_code' => $dataplan->virus,
                'amount' => $sendRequest->amount,
                'phone' => $sendRequest->plan_phone,
                'request_id' => Carbon::now('Africa/Lagos')->format('YmdHi') . substr(md5($data['transid']), 0, 8)
            );
            $endpoints = "https://vtpass.com/api/pay";
            $headers = [
                "Authorization: Basic " . base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password),
                'Content-Type: application/json'
            ];
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            if (!empty($response)) {
                if (isset($response['code'])) {
                    if ($response['code'] == 000) {
                        return 'success';
                    } else {
                        return 'fail';
                    }
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return 'fail';
        }
    }

    public static function Boltnet($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network_d = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $web_api = DB::table('web_api')->first();
            $other_api = DB::table('other_api')->first();

            // BoltNet Network ID from database
            $network_id = $network_d->boltnet_id ?? 1;

            $payload = [
                'network' => $network_id,
                'mobile_number' => $sendRequest->plan_phone,
                'plan' => $dataplan->boltnet,
                'Ported_number' => true
            ];

            $base_url = rtrim($web_api->boltnet_url ?? 'https://boltnet.com.ng', '/');
            $endpoint_details = [
                'endpoint' => $base_url . "/api/data/",
                'token' => $other_api->boltnet_token
            ];

            $response = ApiSending::BoltNetApi($endpoint_details, $payload);

            if (!empty($response)) {
                $res = $response['response'] ?? $response;
                $status = strtolower($res['Status'] ?? $res['status'] ?? '');
                $api_res = $res['api_response'] ?? $res['message'] ?? $res['Message'] ?? '';

                if ($api_res) {
                    DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $api_res]);
                }

                if ($status == 'successful' || $status == 'success') {
                    return 'success';
                } else if ($status == 'processing') {
                    return 'process';
                } else {
                    return 'fail';
                }
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    public static function Zimrax($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network_d = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();

            // Zimrax uses names like MTN, AIRTEL, GLO, ETISALAT
            $net_name = $network_d->network;
            if ($net_name == '9MOBILE') {
                $net_name = 'ETISALAT';
            }

            $payload = [
                'token' => $other_api->zimrax,
                'mobile' => $sendRequest->plan_phone,
                'network' => $net_name,
                'plan_code' => $dataplan->zimrax,
                'request_id' => $data['transid']
            ];

            $response = ApiSending::ZimraxApi(null, $payload); // Endpoint is hardcoded in helper

            if (!empty($response)) {
                if (isset($response['status']) && $response['status'] == 'success') {
                    if (isset($response['desc'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                    }
                    return 'success';
                } else {
                    if (isset($response['desc'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['desc']]);
                    }
                    return 'fail';
                }
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    public static function Hamdala($data)
    {
        if (DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network_d = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
            $other_api = DB::table('other_api')->first();

            // Corrected Mapping based on User Input (1=MTN, 2=AIRTEL, 3=GLO, 4=9MOBILE)
            $net_id = 1; // Default MTN
            if ($network_d->network == 'AIRTEL') {
                $net_id = 2;
            }
            if ($network_d->network == 'GLO') {
                $net_id = 3;
            }
            if ($network_d->network == '9MOBILE' || $network_d->network == 'ETISALAT') {
                $net_id = 4;
            }

            $payload = [
                'phone' => $sendRequest->plan_phone,
                'plan_id' => (int) $dataplan->hamdala, // Cast to integer
                'network_id' => (int) $net_id // Cast to integer
            ];

            $response = ApiSending::HamdalaApi($payload, $other_api->hamdala);

            if (!empty($response)) {
                // Check success indicator.
                if ((isset($response['status']) && $response['status'] == 'success') || (isset($response['message']) && stripos($response['message'], 'successful') !== false)) {
                    if (isset($response['message'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['message']]);
                    }
                    return 'success';
                } else {
                    if (isset($response['message'])) {
                        DB::table('data')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $response['message']]);
                    }
                    return 'fail';
                }
            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }
    }

    // --- UNIVERSAL SMART SWITCH LOGIC ---

    public static function SmartAttempt($primary_method, $data)
    {
        // 1. Try Primary Vendor
        \Log::info("🚨 SmartSwitch: Trying Primary - $primary_method");
        if (method_exists(self::class, $primary_method)) {
            $response = self::$primary_method($data);
        } else {
            \Log::error("SmartSwitch: Method $primary_method does not exist.");
            $response = 'fail';
        }

        // 2. If Failed, Trigger Failover
        if ($response == 'fail') {
            \Log::info("🚨 SmartSwitch: Primary Failed. Initiating Failover Scan...");
            // Exclude primary from retry list
            return self::RetryLogic($data, [$primary_method]);
        }

        return $response;
    }

    public static function RetryLogic($data, $attempted_methods)
    {
        // 1. Get Plan Details
        $dataplan = DB::table('data_plan')->where(['plan_id' => $data['purchase_plan']])->first();
        if (!$dataplan) {
            return 'fail';
        }

        // 2. Define Vendor Map: [Column Name => Method Name]
        $vendor_map = [
            'zimrax' => 'Zimrax',
            'boltnet' => 'Boltnet',
            'hamdala' => 'Hamdala',
            'smeplug' => 'Smeplug',
            'easyaccess' => 'Easy',
            'megasub' => 'Mega',
            'vtpass' => 'Vtpass',
            'habukhan1' => 'Habukhan1',
            'habukhan2' => 'Habukhan2',
            'autopilot' => 'Autopilot'
        ];

        // 3. Find Available Alternates
        foreach ($vendor_map as $column => $method) {
            // If we already tried this method, skip it
            if (in_array($method, $attempted_methods)) {
                continue;
            }

            // Check if Plan ID is configured for this vendor
            if (!empty($dataplan->$column)) {

                \Log::info("🚨 SmartSwitch: Found Alternate - $method (ID: " . $dataplan->$column . ")");

                // Try this vendor
                if (method_exists(self::class, $method)) {
                    $response = self::$method($data);
                } else {
                    \Log::error("SmartSwitch: Method $method does not exist.");
                    $response = 'fail';
                }

                // If success, return success immediately
                if ($response == 'success') {
                    \Log::info("🚨 SmartSwitch: FAILOVER SUCCESS with $method");
                    return 'success';
                } else {
                    \Log::info("🚨 SmartSwitch: $method also failed.");
                    $attempted_methods[] = $method;
                }
            }
        }

        // 4. If all fail
        return 'fail';
    }
}
