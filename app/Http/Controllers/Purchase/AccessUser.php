<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccessUser extends Controller
{
    public function generate(Request $request)
    {
        $d_token = $request->header('Authorization');
        $credentials = $this->decodeToken($d_token);

        if (!$credentials) {
            return $this->unauthorizedResponse('Invalid authorization token format');
        }

        [$username, $password] = $credentials;

        if (empty($username) || empty($password)) {
            return $this->unauthorizedResponse('Username and password must not be empty');
        }

        $user = DB::table('user')->where('username', $username)->first();

        if (!$user) {
            return $this->unauthorizedResponse('Invalid username or password');
        }

        if (!$this->checkPassword($password, $user)) {
            return $this->unauthorizedResponse('Invalid username and password; note that password is sensitive');
        }

        return $this->handleUserStatus($user);
    }

    private function decodeToken($token)
    {
        if (strpos($token, 'Basic ') === false) {
            return null;
        }

        $encoded = trim(str_replace('Basic', '', $token));
        $decoded = base64_decode($encoded);

        // Check if decoding resulted in valid data
        $credentials = explode(':', trim($decoded));
        return count($credentials) === 2 ? $credentials : null; // Ensure there are exactly two parts
    }

    private function checkPassword($password, $user)
    {
        return password_verify($password, $user->password) ||
            $password === $user->password ||
            $this->generateHash($password) === $user->password ||
            md5($password) === $user->password;
    }

    private function generateHash($password)
    {
        return substr(sha1(md5($password)), 3, 10);
    }

    private function handleUserStatus($user)
    {
        switch ($user->status) {
            case 1:
                $this->insert_stock($user->username);
                return response()->json([
                    'status' => 'success',
                    'AccessToken' => $user->apikey,
                    'balance' => number_format($user->bal, 2),
                    'username' => $user->username
                ]);
            case 0:
                return $this->forbiddenResponse('Account not yet verified');
            case 2:
                return $this->forbiddenResponse('Account banned');
            case 3:
                return $this->forbiddenResponse('Account not yet deactivated');
            default:
                return $this->unauthorizedResponse('Unknown account status');
        }
    }

    private function unauthorizedResponse($message)
    {
        return response()->json(['status' => 'fail', 'message' => $message])->setStatusCode(403);
    }

    private function forbiddenResponse($message)
    {
        return response()->json(['status' => 'fail', 'message' => $message])->setStatusCode(403);
    }
}
