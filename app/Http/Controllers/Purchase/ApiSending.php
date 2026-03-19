<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\DB;

class ApiSending extends Controller
{

    public static function HabukhanApi($data, $sending_data)
    {
        // Log the incoming data for debugging
        \Log::info('HabukhanApi Debug - Input Data:', [
            'website_url' => $data['website_url'],
            'endpoint' => $data['endpoint'],
            'sending_data' => $sending_data
        ]);
        
        // Detect if this is an electricity/bill call (endpoint contains /api/bill)
        $is_bill = (strpos($data['endpoint'], '/api/bill') !== false);
        
        // Step 1: Login to get access token
        $login_url = $data['website_url'] . "/api/login/verify/user";
        \Log::info('HabukhanApi Debug - Login URL:', ['url' => $login_url]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        // Decode the basic auth to get username and password
        $decoded_auth = base64_decode($data['accessToken']);
        list($username, $password) = explode(':', $decoded_auth);
        
        $login_payload = json_encode([
            'username' => $username,
            'password' => $password
        ]);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $json = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        \Log::info('HabukhanApi Debug - Login Response:', [
            'http_code' => $http_code,
            'curl_error' => $curl_error
        ]);
        
        $decode_habukhan = json_decode($json, true);
        
        if (!empty($decode_habukhan)) {
            if (isset($decode_habukhan['token']) && $decode_habukhan['status'] == 'success') {
                $access_token = $decode_habukhan['token'];
                $api_key = $decode_habukhan['user']['apikey'] ?? null;
                $user_pin = $decode_habukhan['user']['pin'] ?? null;
                
                \Log::info('HabukhanApi Debug - Login Success:', [
                    'is_bill' => $is_bill,
                    'has_apikey' => !empty($api_key),
                    'has_pin' => !empty($user_pin),
                    'user_balance' => $decode_habukhan['user']['bal'] ?? 'N/A'
                ]);
                
                // Step 2: Build payload based on service type
                if ($is_bill) {
                    // ELECTRICITY: Use same API key + Origin approach as other services
                    // The Habukhan docs show pin+user_id for USER flow, but for 
                    // external API integration we use Token auth + Origin header
                    // (same as airtime/data/cable which work correctly)
                    if (!$api_key) {
                        \Log::error('HabukhanApi - API Key not found for bill transaction');
                        return ['status' => 'fail', 'message' => 'API Key not found'];
                    }
                    
                    // Remove PIN if present, add unique request-id
                    if (isset($sending_data['pin'])) {
                        unset($sending_data['pin']);
                    }
                    $unique_request_id = ($sending_data['request-id'] ?? 'TXN') . '_' . time() . '_' . uniqid();
                    $sending_data['request-id'] = $unique_request_id;
                    
                    $final_payload = $sending_data;
                    
                    $headers = [
                        "Authorization: Token $api_key",
                        'Content-Type: application/json',
                        'Origin: https://oyitipay.com'
                    ];
                    
                    \Log::info('HabukhanApi - Bill Payload (API key + Origin):', [
                        'payload_keys' => array_keys($final_payload),
                        'disco' => $final_payload['disco'] ?? 'N/A',
                        'meter_number' => $final_payload['meter_number'] ?? 'N/A',
                        'amount' => $final_payload['amount'] ?? 'N/A'
                    ]);
                } else {
                    // OTHER SERVICES (data, airtime, cable, etc): Use API key + Origin header
                    if (!$api_key) {
                        \Log::error('HabukhanApi - API Key not found for non-bill transaction');
                        return ['status' => 'fail', 'message' => 'API Key not found'];
                    }
                    
                    // Remove PIN if present, add unique request-id
                    if (isset($sending_data['pin'])) {
                        unset($sending_data['pin']);
                    }
                    $unique_request_id = ($sending_data['request-id'] ?? 'TXN') . '_' . time() . '_' . uniqid();
                    $sending_data['request-id'] = $unique_request_id;
                    
                    $final_payload = $sending_data;
                    
                    $headers = [
                        "Authorization: Token $api_key",
                        'Content-Type: application/json',
                        'Origin: https://oyitipay.com'
                    ];
                }
                
                // Step 3: Make the actual transaction call
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $data['endpoint']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($final_payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $is_bill ? 60 : 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                \Log::info('HabukhanApi Debug - Transaction Request:', [
                    'url' => $data['endpoint'],
                    'is_bill' => $is_bill,
                    'payload_keys' => array_keys($final_payload)
                ]);
                
                $dataapi = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                \Log::info('HabukhanApi Debug - Transaction Response:', [
                    'http_code' => $httpcode,
                    'response' => $dataapi,
                    'curl_error' => $curl_error
                ]);
                
                $response = json_decode($dataapi, true);
                
                // Extract token from Habukhan bill response data if present
                if ($is_bill && !empty($response) && isset($response['status']) && $response['status'] == 'success' && isset($response['data']['token'])) {
                    $response['token'] = $response['data']['token'];
                }
                
                return $response;

            } else {
                \Log::error('HabukhanApi Debug - Login Failed:', [
                    'response' => $decode_habukhan
                ]);
                return ['status' => 'fail', 'message' => 'Login failed: ' . ($decode_habukhan['message'] ?? 'Unknown error')];
            }
        } else {
            \Log::error('HabukhanApi Debug - Empty Response:', [
                'raw_response' => $json,
                'http_code' => $http_code
            ]);
            return ['status' => 'fail', 'message' => 'No response from login endpoint'];
        }
    }

    public static function AdexApi($data, $sending_data)
    {
        // Step 1: Get AccessToken using Basic Authentication
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['website_url'] . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Authorization: Basic " . $data['accessToken'] . "",
            ]
        );
        
        \Log::info('Adex Auth Request:', [
            'url' => $data['website_url'] . "/api/user/",
            'auth_header' => "Basic " . $data['accessToken']
        ]);
        
        $json = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        \Log::info('Adex Auth Response:', [
            'http_code' => $httpcode,
            'response' => $json
        ]);
        
        $decode_adex = (json_decode($json, true));
        if (!empty($decode_adex)) {
            if (isset($decode_adex['AccessToken'])) {
                $access_token = $decode_adex['AccessToken'];
                
                \Log::info('Adex AccessToken obtained:', [
                    'token' => substr($access_token, 0, 20) . '...'
                ]);
                
                // Step 2: Make the actual API call using the AccessToken
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $data['endpoint']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sending_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $headers = [
                    "Authorization: Token $access_token",
                    'Content-Type: application/json'
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                \Log::info('Adex API Request:', [
                    'url' => $data['endpoint'],
                    'payload' => $sending_data,
                    'headers' => $headers
                ]);
                
                $dataapi = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                \Log::info('Adex API Response:', [
                    'http_code' => $httpcode,
                    'response' => $dataapi
                ]);
                
                return json_decode($dataapi, true);

            } else {
                \Log::error('Adex Auth Failed: No AccessToken in response', [
                    'response' => $decode_adex
                ]);
                return ['status' => 'fail', 'message' => 'Authentication failed - no AccessToken'];
            }
        } else {
            \Log::error('Adex Auth Failed: Empty or invalid response', [
                'http_code' => $httpcode,
                'raw_response' => $json
            ]);
            return ['status' => 'fail', 'message' => 'Authentication failed - empty response'];
        }
    }

    public static function MSORGAPI($endpoint, $data)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint['endpoint']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            "Authorization: Token " . $endpoint['token'],
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $dataapi = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        return json_decode($dataapi, true);

    }

    public static function BoltNetApi($endpoint, $data)
    {
        \Log::info('BoltNet API Request:', ['url' => $endpoint['endpoint'], 'payload' => $data]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint['endpoint']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            "Authorization: Token " . $endpoint['token'],
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $dataapi = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($dataapi, true);
        \Log::info('BoltNet API Response:', ['response' => $response]);
        return $response;
    }

    public static function VIRUSAPI($endpoint, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint['endpoint']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $dataapi = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == 200 || $httpcode == 201) {
            // file_put_contents('status.txt', $httpcode);
            // file_put_contents('message.txt', $dataapi);
            return json_decode($dataapi, true);
        } else {
            return ['status' => 'fail'];
        }
    }

    public static function ZimraxApi($endpoint, $payload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://zimrax.com/api/data");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public static function HamdalaApi($payload, $token)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://hamdalavtu.com.ng/api/v1/data");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Expect:' // Fix for 417 Expectation Failed
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($result, true);
    }

    public static function OTHERAPI($endpoint, $payload, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (isset($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $dataapi = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($dataapi === false) {
            file_put_contents('curl_error.txt', curl_error($ch));
        }
        file_put_contents('status.txt', $httpcode);
        file_put_contents('message.txt', $dataapi);

        return json_decode($dataapi, true);

    }
    public static function ADMINEMAIL($data)
    {
        if (DB::table('user')->where(['status' => 1, 'type' => 'ADMIN'])->count() != 0) {
            $all_admin = DB::table('user')->where(['status' => 1, 'type' => 'ADMIN'])->get();
            $sets = DB::table('general')->first();
            foreach ($all_admin as $admin) {
                $email_data = [
                    'email' => $admin->email,
                    'username' => $admin->username,
                    'title' => $data['title'],
                    'sender_mail' => $sets->app_email,
                    'app_name' => $sets->app_name,
                    'mes' => $data['mes']
                ];
                MailController::send_mail($email_data, 'email.purchase');
                return ['status' => 'success'];
            }
        } else {
            return ['status' => 'fail'];
        }
    }
}
