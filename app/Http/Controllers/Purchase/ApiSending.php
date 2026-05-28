<?php
namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiSending extends Controller
{

    /**
     * Perform Habukhan login and return ['api_key' => ..., 'access_token' => ...].
     * Result is cached per username for 25 minutes to avoid a login round-trip on every purchase.
     */
    private static function habukhanLogin(string $loginUrl, string $username, string $password): ?array
    {
        $cacheKey = 'habukhan_token_' . md5($loginUrl . $username);

        // Return cached credentials if still valid
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $username, 'password' => $password]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $json      = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($json, true);

        if (
            !empty($decoded) &&
            isset($decoded['token']) &&
            $decoded['status'] === 'success' &&
            !empty($decoded['user']['apikey'])
        ) {
            $credentials = [
                'access_token' => $decoded['token'],
                'api_key'      => $decoded['user']['apikey'],
            ];
            // Cache for 25 minutes (tokens typically last 30–60 min)
            Cache::put($cacheKey, $credentials, now()->addMinutes(25));
            return $credentials;
        }

        \Log::error('HabukhanApi - Login Failed:', ['http_code' => $http_code, 'response' => $decoded]);
        return null;
    }

    public static function HabukhanApi($data, $sending_data)
    {
        // Sanitize base URL
        $baseUrl = rtrim(trim($data['website_url']), '/');
        if (!preg_match("~^(?:f|ht)tps?://~i", $baseUrl)) {
            $baseUrl = "https://" . $baseUrl;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Authorization: Basic " . $data['accessToken'] . "",
                "Accept: application/json"
            ]
        );
        $json = curl_exec($ch);
        
        if ($json === false) {
            \Log::error('HabukhanApi Auth Error: ' . curl_error($ch));
        }
        curl_close($ch);
        
        $decode_habukhan = (json_decode($json, true));
        if (!empty($decode_habukhan)) {
            if (isset($decode_habukhan['AccessToken'])) {
                $access_token = $decode_habukhan['AccessToken'];
                
                // Parse endpoint URL
                $endpointUrl = rtrim(trim($data['endpoint']), '/');
                if (!preg_match("~^(?:f|ht)tps?://~i", $endpointUrl)) {
                    $endpointUrl = "https://" . $endpointUrl;
                }
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpointUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sending_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $headers = [
                    "Authorization: Token $access_token",
                    'Content-Type: application/json',
                    "Accept: application/json"
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $dataapi = curl_exec($ch);
                
                if ($dataapi === false) {
                    \Log::error('HabukhanApi Data Error: ' . curl_error($ch));
                }
                
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $res = json_decode($dataapi, true);

                $is_bill = (strpos($data['endpoint'], '/api/bill') !== false);
                if ($is_bill && !empty($res) && isset($res['status']) && $res['status'] == 'success' && isset($res['data']['token'])) {
                    $res['token'] = $res['data']['token'];
                }

                return $res;

            } else {
                \Log::warning('HabukhanApi Auth Failed: ', ['response' => $json]);
                return ['status' => 'fail'];
            }
        } else {
            \Log::warning('HabukhanApi Auth Returned Empty or Invalid JSON: ', ['raw' => $json]);
            return ['status' => 'fail'];
        }
    }

    public static function AdexApi($data, $sending_data)
    {
        // Step 1: Get AccessToken using Basic Authentication
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data['website_url'] . "/api/user/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . $data['accessToken'],
        ]);

        $json = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decode_adex = json_decode($json, true);
        if (!empty($decode_adex)) {
            if (isset($decode_adex['AccessToken'])) {
                $access_token = $decode_adex['AccessToken'];

                // Step 2: Make the actual API call using the AccessToken
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $data['endpoint']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sending_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Token $access_token",
                    'Content-Type: application/json'
                ]);

                $dataapi = curl_exec($ch);
                curl_close($ch);

                return json_decode($dataapi, true);

            } else {
                \Log::error('AdexApi - Auth Failed: No AccessToken in response', ['response' => $decode_adex]);
                return ['status' => 'fail', 'message' => 'Authentication failed - no AccessToken'];
            }
        } else {
            \Log::error('AdexApi - Auth Failed: Empty response', ['http_code' => $httpcode]);
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
        \Log::info('🚨 BoltNet API Request:', ['url' => $endpoint['endpoint'] ?? 'N/A', 'payload' => $data]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint['endpoint']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $headers = [
            "Authorization: Token " . ($endpoint['token'] ?? ''),
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $dataapi = curl_exec($ch);
        $err = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            \Log::error('🚨 BoltNet API cURL Error:', ['error' => $err]);
            return ['status' => 'fail', 'message' => 'Connection Error'];
        }

        $decoded = json_decode($dataapi, true);
        \Log::info('🚨 BoltNet API Response:', ['http_code' => $httpcode, 'response' => $decoded]);
        
        return $decoded;
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
