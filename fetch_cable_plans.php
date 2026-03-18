<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$api = DB::table('web_api')->first();
$hab = DB::table('habukhan_api')->first();
$token = base64_encode($hab->habukhan1_username . ':' . $hab->habukhan1_password);

$ch = curl_init($api->habukhan_website1 . '/api/cable/cable-list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Token ' . $token, 'Content-Type: application/json']);
$res = curl_exec($ch);
curl_close($ch);

print_r(json_decode($res, true));
