<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;



class MeterSend extends Controller
{
    public static function Habukhan1($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website1 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan1;
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $send_request);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Habukhan');
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);

        $response = json_decode($query, true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan2($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website2 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan2;
        $response = json_decode(file_get_contents($send_request), true);

        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan3($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website3 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan3;
        $response = json_decode(file_get_contents($send_request), true);
        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan4($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website4 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan4;
        $response = json_decode(file_get_contents($send_request), true);

        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Habukhan5($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website5 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan5;
        $response = json_decode(file_get_contents($send_request), true);

        if (!empty($response)) {
            if (!empty($response['name'])) {
                return $response['name'];
            }
        }
    }
    public static function Email($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $send_request = $api_website->habukhan_website1 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . $bill_plan->habukhan1;
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
        $bill = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $vtpass_token = base64_encode($other_api->vtpass_username . ":" . $other_api->vtpass_password);
        $postdata = array(
            'serviceID' => $bill->vtpass,
            'billersCode' => $data['meter_number'],
            'type' => $data['meter_type']
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://vtpass.com/api/merchant-verify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $headers = [
            'Authorization: Basic ' . $vtpass_token . '',
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $request = curl_exec($ch);
        curl_close($ch);
        $response = (json_decode($request, true));
        if (!empty($response)) {
            if (!empty($response['content']['Customer_Name'])) {
                return $response['content']['Customer_Name'];
            }
        }
    }

    public static function Adex1($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex1_username . ":" . $adex_api->adex1_password);
        
        $send_request = $api_website->adex_website1 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . ($bill_plan->adex1 ?? $bill_plan->habukhan1);
        
        \Log::info('Adex1 Meter Verification Request:', ['url' => $send_request]);
        
        // Get AccessToken
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_website->adex_website1 . "/api/user/");
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
            
            \Log::info('Adex1 Meter Verification Response:', ['response' => $response]);
            
            if (!empty($response) && !empty($response['name'])) {
                return $response['name'];
            }
        }
        
        return null;
    }

    public static function Adex2($data)
    {
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex2_username . ":" . $adex_api->adex2_password);
        
        $send_request = $api_website->adex_website2 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . ($bill_plan->adex2 ?? $bill_plan->habukhan2);
        
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
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex3_username . ":" . $adex_api->adex3_password);
        
        $send_request = $api_website->adex_website3 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . ($bill_plan->adex3 ?? $bill_plan->habukhan3);
        
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
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex4_username . ":" . $adex_api->adex4_password);
        
        $send_request = $api_website->adex_website4 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . ($bill_plan->adex4 ?? $bill_plan->habukhan4);
        
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
        $bill_plan = DB::table('bill_plan')->where('plan_id', $data['disco'])->first();
        $api_website = DB::table('web_api')->first();
        $adex_api = DB::table('adex_api')->first();
        $accessToken = base64_encode($adex_api->adex5_username . ":" . $adex_api->adex5_password);
        
        $send_request = $api_website->adex_website5 . "/api/bill/bill-validation?meter_type=" . $data['meter_type'] . "&meter_number=" . $data['meter_number'] . "&disco=" . ($bill_plan->adex5 ?? $bill_plan->habukhan5);
        
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
