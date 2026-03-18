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
$headers = [
    'Authorization: Token ' . $apikey,
    'Content-Type: application/json',
    'Origin: https://oyitipay.com'
];

echo "Logged in OK. Trying multiple endpoints...\n\n";

// Try multiple possible endpoints
$endpoints = [
    '/api/cable/cable-list',
    '/api/cable/plans',
    '/api/cable-plan',
    '/api/cable/cable-plan',
    '/website/app/cable',
    '/api/cable/',
];

foreach ($endpoints as $ep) {
    echo "--- Trying: $base$ep ---\n";
    $ch = curl_init($base . $ep);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP $code: " . substr($res, 0, 500) . "\n\n";
}
