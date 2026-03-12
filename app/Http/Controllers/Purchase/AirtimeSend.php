<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AirtimeSend extends Controller
{
    public static function Autopilot($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();

            // Map airtime types to Autopilot naming
            $type = strtoupper($sendRequest->network_type);
            if ($type == 'SHARE AND SELL') {
                $type = 'SNS';
            }
            // Default to VTU if not SNS or others
            if (!in_array($type, ['VTU', 'SNS', 'AWUF'])) {
                $type = 'VTU';
            }

            $reference = (new Controller)->generateAutopilotReference();
            $payload = [
                'networkId' => (string) $network->autopilot_id,
                'airtimeType' => $type,
                'amount' => (string) $sendRequest->amount,
                'phone' => $sendRequest->plan_phone,
                'reference' => $reference
            ];

            // Store reference immediately
            DB::table('airtime')->where('transid', $data['transid'])->update(['api_reference' => $reference]);

            \Log::info('Autopilot Airtime REQUEST:', ['payload' => $payload, 'transid' => $data['transid']]);

            $response = (new Controller)->autopilot_request('/v1/airtime', $payload);

            \Log::info('Autopilot Airtime RESPONSE:', ['response' => $response, 'transid' => $data['transid']]);

            if (!empty($response)) {
                // Autopilot uses both 'status' and 'code' fields
                // Success: status=true AND code=200
                // Partial: status=true AND code=201 (only for A2C, treat as process)
                // Failed: status=false OR code=424
                $status = $response['status'] ?? false;
                $code = $response['code'] ?? 0;

                if ($status == true && $code == 200) {
                    \Log::info('Autopilot Airtime: Returning SUCCESS', ['transid' => $data['transid']]);
                    return 'success';
                } else if ($status == false || $code == 424) {
                    \Log::info('Autopilot Airtime: Returning FAIL', ['transid' => $data['transid'], 'code' => $code, 'message' => $response['data']['message'] ?? 'No message']);
                    return 'fail';
                } else {
                    \Log::info('Autopilot Airtime: Returning PROCESS (code=' . $code . ')', ['transid' => $data['transid'], 'response' => $response]);
                    return 'process';
                }
            } else {
                \Log::info('Autopilot Airtime: Returning PROCESS (empty response)', ['transid' => $data['transid']]);
            }
            return 'process';
        } else {
            return 'fail';
        }
    }

    public static function Habukhan1($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan1_username . ":" . $habukhan_api->habukhan1_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'plan_type' => strtoupper($sendRequest->network_type),
                'bypass' => true,
                'amount' => $sendRequest->amount,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website1,
                'endpoint' => $api_website->habukhan_website1 . "/api/topup/",
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan2_username . ":" . $habukhan_api->habukhan2_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'plan_type' => strtoupper($sendRequest->network_type),
                'bypass' => true,
                'amount' => $sendRequest->amount,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website2,
                'endpoint' => $api_website->habukhan_website2 . "/api/topup/",
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan3_username . ":" . $habukhan_api->habukhan3_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'plan_type' => strtoupper($sendRequest->network_type),
                'bypass' => true,
                'amount' => $sendRequest->amount,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website3,
                'endpoint' => $api_website->habukhan_website3 . "/api/topup/",
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan4_username . ":" . $habukhan_api->habukhan4_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'plan_type' => strtoupper($sendRequest->network_type),
                'bypass' => true,
                'amount' => $sendRequest->amount,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website4,
                'endpoint' => $api_website->habukhan_website4 . "/api/topup/",
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $habukhan_api = DB::table('habukhan_api')->first();
            $accessToken = base64_encode($habukhan_api->habukhan5_username . ":" . $habukhan_api->habukhan5_password);
            $paypload = array(
                'network' => $network->habukhan_id,
                'phone' => $sendRequest->plan_phone,
                'plan_type' => strtoupper($sendRequest->network_type),
                'bypass' => true,
                'amount' => $sendRequest->amount,
                'request-id' => $data['transid']
            );

            $admin_details = [
                'website_url' => $api_website->habukhan_website5,
                'endpoint' => $api_website->habukhan_website5 . "/api/topup/",
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
    public static function Msorg1($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'airtime_type' => strtoupper($sendRequest->network_type),
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website1 . "/api/topup/",
                'token' => $msorg_api->msorg1
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        $plan_status = 'success';
                    } else if ($response['Status'] == 'failed') {
                        $plan_status = 'fail';
                    } else {
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'airtime_type' => strtoupper($sendRequest->network_type),
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website2 . "/api/topup/",
                'token' => $msorg_api->msorg2
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        $plan_status = 'success';
                    } else if ($response['Status'] == 'failed') {
                        $plan_status = 'fail';
                    } else {
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'airtime_type' => strtoupper($sendRequest->network_type),
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website3 . "/api/topup/",
                'token' => $msorg_api->msorg3
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        $plan_status = 'success';
                    } else if ($response['Status'] == 'failed') {
                        $plan_status = 'fail';
                    } else {
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'airtime_type' => strtoupper($sendRequest->network_type),
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website4 . "/api/topup/",
                'token' => $msorg_api->msorg4
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        $plan_status = 'success';
                    } else if ($response['Status'] == 'failed') {
                        $plan_status = 'fail';
                    } else {
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $msorg_api = DB::table('msorg_api')->first();
            $paypload = array(
                'network' => $network->msorg_id,
                'mobile_number' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'airtime_type' => strtoupper($sendRequest->network_type),
                'Ported_number' => true,
            );
            $admin_details = [
                'endpoint' => $api_website->msorg_website5 . "/api/topup/",
                'token' => $msorg_api->msorg5
            ];
            $response = ApiSending::MSORGAPI($admin_details, $paypload);
            // declare plan status
            if (!empty($response)) {
                if (isset($response['Status'])) {
                    if ($response['Status'] == 'successful' || $response['Status'] == 'processing') {
                        $plan_status = 'success';
                    } else if ($response['Status'] == 'failed') {
                        $plan_status = 'fail';
                    } else {
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();

            $paypload = array(
                'network' => $network->virus_id,
                'mobile' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'token' => $virus_api->virus1,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website1 . "/api/airtime",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();

            $paypload = array(
                'network' => $network->virus_id,
                'mobile' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'token' => $virus_api->virus2,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website2 . "/api/airtime",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();

            $paypload = array(
                'network' => $network->virus_id,
                'mobile' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'token' => $virus_api->virus3,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website3 . "/api/airtime",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();

            $paypload = array(
                'network' => $network->virus_id,
                'mobile' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'token' => $virus_api->virus4,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website4 . "/api/airtime",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $api_website = DB::table('web_api')->first();
            $virus_api = DB::table('virus_api')->first();

            $paypload = array(
                'network' => $network->virus_id,
                'mobile' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'token' => $virus_api->virus5,
                'request_id' => $data['transid']
            );
            $admin_details = [
                'endpoint' => $api_website->virus_website5 . "/api/airtime",
            ];
            $response = ApiSending::VIRUSAPI($admin_details, $paypload);
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
    public static function Smeplug($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
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
                'amount' => $sendRequest->amount,
                "customer_reference" => $sendRequest->transid
            );
            $endpoints = "https://smeplug.ng/api/v1/airtime/purchase";
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $other_api = DB::table('other_api')->first();

            $paypload = array(
                'process' => "buy",
                'recipient' => $sendRequest->plan_phone,
                'api_key' => $other_api->simserver,
                'amount' => $sendRequest->amount,
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
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
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
                'amount' => $sendRequest->amount,
                'type' => strtolower($sendRequest->network_type),
                'reference' => $data['transid']
            );
            $endpoints = "https://simhosting.ogdams.ng/api/v1/vend/airtime";
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

    public function Vtpass($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $other_api = DB::table('other_api')->first();

            $serviceID = strtolower($network->virus_id);


            $paypload = array(
                'serviceID' => $serviceID,
                'phone' => $sendRequest->plan_phone,
                'amount' => $sendRequest->amount,
                'request_id' => Carbon::now('Africa/Lagos')->format('YmdHi') . substr(md5($data['transid']), 0, 8)
            );
            $endpoints = "https://vtpass.com/api/pay";
            $headers = [
                "Authorization: Basic " . base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password),
                'Content-Type: application/json'
            ];

            // Log for debugging
            \Log::info('VTPass Airtime SENDING:', ['url' => $endpoints, 'payload' => $paypload]);
            $response = ApiSending::OTHERAPI($endpoints, $paypload, $headers);
            \Log::info('VTPass Airtime RECEIVED:', ['response' => $response]);

            // declare plan status
            if (!empty($response)) {
                $code = $response['code'] ?? '';
                // Handle various VTPASS success indicators (loose comparison like BillSend)
                if ($code == '000' || $code == 'success') {
                    $plan_status = 'success';
                } else if ($code == '099') {
                    $plan_status = 'process';
                } else {
                    $plan_status = 'fail';
                }
            } else {
                $plan_status = 'fail';
            }
            return $plan_status;
        } else {
            return 'fail';
        }
    }
    public static function Email($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $message = strtoupper($sendRequest->username) . ' wants to buy ' . $network->network . ' ' . $sendRequest->network_type . ' ₦' . number_format($sendRequest->amount, 2) . ' to ' . $sendRequest->plan_phone . '.  Refreence is ' . $sendRequest->transid;
            $datas = [
                'mes' => $message,
                'title' => 'AIRTIME PURCHASE'
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

    public static function Boltnet($data)
    {
        if (DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->count() == 1) {
            $sendRequest = DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->first();
            $network_d = DB::table('network')->where(['network' => $sendRequest->network])->first();
            $web_api = DB::table('web_api')->first();
            $other_api = DB::table('other_api')->first();

            // BoltNet Network ID from database
            $network_id = $network_d->boltnet_id ?? 1;

            $payload = [
                'network' => $network_id,
                'amount' => $sendRequest->amount,
                'mobile_number' => $sendRequest->plan_phone,
                'Ported_number' => true,
                'airtime_type' => 'VTU'
            ];

            $base_url = rtrim($web_api->boltnet_url ?? 'https://boltnet.com.ng', '/');
            $endpoint_details = [
                'endpoint' => $base_url . "/api/topup/",
                'token' => $other_api->boltnet_token
            ];

            $response = ApiSending::BoltNetApi($endpoint_details, $payload);

            // Log for debugging BoltNet status
            \Log::info('BoltNet Airtime Response:', ['response' => $response]);

            if (!empty($response)) {
                $res = $response['response'] ?? $response;
                $status = strtolower($res['Status'] ?? $res['status'] ?? '');
                $api_res = $res['api_response'] ?? $res['message'] ?? $res['Message'] ?? '';

                if ($api_res) {
                    DB::table('airtime')->where(['username' => $data['username'], 'transid' => $data['transid']])->update(['api_response' => $api_res]);
                }

                if ($status == 'successful' || $status == 'success' || $status == 'completed') {
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
}