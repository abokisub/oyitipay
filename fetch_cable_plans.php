<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$api = DB::table('web_api')->first();
$hab = DB::table('habukhan_api')->first();
$base = $api->habukhan_website1;
$username = $hab->habukhan1_username;
$password = $hab->habukhan1_password;

// Step 1: Login
$ch = curl_init($base . '/api/login/verify/user');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $username, 'password' => $password]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$login = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($login['user']['apikey'])) {
    echo "Login failed\n";
    print_r($login);
    exit;
}

$apikey = $login['user']['apikey'];
echo "Logged in OK. Fetching cable plans...\n\n";

// Step 2: Fetch cable plans
$ch = curl_init($base . '/api/cable/cable-list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Token ' . $apikey,
    'Content-Type: application/json',
    'Origin: https://oyitipay.com'
]);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
print_r($data);
