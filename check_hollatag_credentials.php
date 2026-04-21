<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Hollatag BulkSMS Credentials Check ===\n\n";

$api = DB::table('other_api')->first();

if ($api) {
    echo "Current Hollatag Credentials:\n";
    echo "Username: " . ($api->hollatag_username ?? 'NOT SET') . "\n";
    echo "Password: " . ($api->hollatag_password ?? 'NOT SET') . "\n\n";
    
    echo "The API returned 'error_user' which means these credentials are incorrect.\n\n";
    echo "To fix this, you need to:\n";
    echo "1. Get the correct Hollatag username and password\n";
    echo "2. Update them in the admin dashboard or run this SQL:\n\n";
    echo "UPDATE other_api SET \n";
    echo "  hollatag_username = 'YOUR_CORRECT_USERNAME',\n";
    echo "  hollatag_password = 'YOUR_CORRECT_PASSWORD'\n";
    echo "WHERE id = 1;\n\n";
} else {
    echo "❌ No record found in other_api table!\n";
}
