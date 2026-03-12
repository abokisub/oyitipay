<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class IUCsend extends Controller
{
    public static function Autopilot($data)
    {
        $cable = DB::table('cable_id')->where('plan_id', $data['cable'])->first();
        $payload = [
            'networkId' => $cable->autopilot_id,
            'smartCardNo' => $data['iuc']
        ];

        $response = (new Controller)->autopilot_request('/v1/validate/smartcard-no', $payload);

        if (!empty($response)) {
            if (isset($response['status']) && $response['status'] == true) {
                if (isset($response['data']['customerName'])) {
                    return $response['data']['customerName'];
                }
            }
        }
        return null;
    }

    public static function Habukhan1($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website1 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan2($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website2 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan3($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website3 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan4($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website4 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan5($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website5 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Email($data)
    {
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website1 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Vtpass($data)
    {
        $other_api = DB::table('other_api')->first();
        $cable = DB::table('cable_id')->where('plan_id', $data['cable'])->first();
        if ($cable->cable_name == 'STARTIME') {
            $cable_name = 'startimes';
        } else {
            $cable_name = strtolower($cable->cable_name);
        }
        $vtpass_token = base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password);
        $postdata = array(
            'serviceID' => $cable_name,
            'billersCode' => $data['iuc'],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://vtpass.com/api/merchant-verify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Increased
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for some local setups
        $headers = [
            'Authorization: Basic ' . $vtpass_token . '',
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $request = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

        if ($curl_errno > 0) {
            return null; // Curl error
        }

        $response = (json_decode($request, true));
        if (!empty($response)) {
            if (isset($response['content']['Customer_Name'])) {
                return $response['content']['Customer_Name'];
            }
            // Fallback for other potential response structures
            if (isset($response['name']))
                return $response['name'];
            if (isset($response['customer_name']))
                return $response['customer_name'];
        }
    }

    public static function Showmax($data)
    {
        // VTpass does not support merchant-verify for Showmax
        // Return a generic message since validation is not available
        return "Showmax Subscriber";
    }

}
