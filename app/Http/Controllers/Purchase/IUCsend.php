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

    public static function Adex1($data)
    {
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex1_username . ":" . $adex_api->adex1_password);
        
        // Use authenticated request for verification since endpoint requires auth
        $send_request = $api_website->adex_website1 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        
        \Log::info('Adex1 Cable Verification Request:', [
            'url' => $send_request
        ]);
        
        // Step 1: Get AccessToken using Basic Authentication
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website1 . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . $accessToken
        ]);
        
        $json = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex) && isset($decode_adex['AccessToken'])) {
            $api_token = $decode_adex['AccessToken'];
            
            // Step 2: Make verification request with API token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $send_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Token " . $api_token
            ]);
            
            $response_data = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response_data, true);
            
            \Log::info('Adex1 Cable Verification Response:', ['response' => $response]);
            
            if (!empty($response)) {
                if (!empty($response['name'])) {
                    return $response['name'];
                }
            }
        }
        
        return null;
    }

    public static function Adex2($data)
    {
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex2_username . ":" . $adex_api->adex2_password);
        
        $send_request = $api_website->adex_website2 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        
        // Get AccessToken
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website2 . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $accessToken]);
        
        $json = curl_exec($ch);
        curl_close($ch);
        
        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex) && isset($decode_adex['AccessToken'])) {
            $api_token = $decode_adex['AccessToken'];
            
            // Make verification request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $send_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token " . $api_token]);
            
            $response_data = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response_data, true);
            if (!empty($response) && !empty($response['name'])) {
                return $response['name'];
            }
        }
        return null;
    }

    public static function Adex3($data)
    {
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex3_username . ":" . $adex_api->adex3_password);
        
        $send_request = $api_website->adex_website3 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website3 . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $accessToken]);
        
        $json = curl_exec($ch);
        curl_close($ch);
        
        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex) && isset($decode_adex['AccessToken'])) {
            $api_token = $decode_adex['AccessToken'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $send_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token " . $api_token]);
            
            $response_data = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response_data, true);
            if (!empty($response) && !empty($response['name'])) {
                return $response['name'];
            }
        }
        return null;
    }

    public static function Adex4($data)
    {
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex4_username . ":" . $adex_api->adex4_password);
        
        $send_request = $api_website->adex_website4 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website4 . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $accessToken]);
        
        $json = curl_exec($ch);
        curl_close($ch);
        
        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex) && isset($decode_adex['AccessToken'])) {
            $api_token = $decode_adex['AccessToken'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $send_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token " . $api_token]);
            
            $response_data = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response_data, true);
            if (!empty($response) && !empty($response['name'])) {
                return $response['name'];
            }
        }
        return null;
    }

    public static function Adex5($data)
    {
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex5_username . ":" . $adex_api->adex5_password);
        
        $send_request = $api_website->adex_website5 . "/api/cable/cable-validation?iuc=" . $data['iuc'] . "&cable=" . $data['cable'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website5 . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . $accessToken]);
        
        $json = curl_exec($ch);
        curl_close($ch);
        
        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex) && isset($decode_adex['AccessToken'])) {
            $api_token = $decode_adex['AccessToken'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $send_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Token " . $api_token]);
            
            $response_data = curl_exec($ch);
            curl_close($ch);
            
            $response = json_decode($response_data, true);
            if (!empty($response) && !empty($response['name'])) {
                return $response['name'];
            }
        }
        return null;
    }

}
