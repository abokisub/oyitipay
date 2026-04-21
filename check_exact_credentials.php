<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$api = DB::table('other_api')->first();

echo "=== Exact Database Values ===\n";
echo "Username: '" . $api->hollatag_username . "'\n";
echo "Username Length: " . strlen($api->hollatag_username) . "\n";
echo "Username Hex: " . bin2hex($api->hollatag_username) . "\n\n";

echo "Password: '" . $api->hollatag_password . "'\n";
echo "Password Length: " . strlen($api->hollatag_password) . "\n";
echo "Password Hex: " . bin2hex($api->hollatag_password) . "\n\n";

echo "Expected:\n";
echo "Username: 'Oyitipay' (length: 8)\n";
echo "Password: 'Apple123' (length: 8)\n\n";

if (trim($api->hollatag_username) !== $api->hollatag_username) {
    echo "⚠️  WARNING: Username has leading/trailing spaces!\n";
}
if (trim($api->hollatag_password) !== $api->hollatag_password) {
    echo "⚠️  WARNING: Password has leading/trailing spaces!\n";
}

echo "\nTo fix spaces, run:\n";
echo "UPDATE other_api SET hollatag_username = TRIM(hollatag_username), hollatag_password = TRIM(hollatag_password);\n";
