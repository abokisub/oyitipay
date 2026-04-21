<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BulkSMS Provider Configuration ===\n\n";

$sel = DB::table('bulksms_sel')->first();
if ($sel) {
    echo "Current Provider: " . $sel->bulksms . "\n\n";
}

echo "Available methods in BulksmsSend class:\n";
echo "- Habukhan1\n";
echo "- Habukhan2\n";
echo "- Habukhan3\n";
echo "- Habukhan4\n";
echo "- Habukhan5\n";
echo "- Hollatag\n";
echo "- Adex1\n";
echo "- Adex2\n";
echo "- Adex3\n";
echo "- Adex4\n";
echo "- Adex5\n\n";

echo "=== Hollatag Credentials ===\n";
$api = DB::table('other_api')->first();
if ($api) {
    echo "Username: " . ($api->hollatag_username ?? 'NOT SET') . "\n";
    echo "Password: " . ($api->hollatag_password ?? 'NOT SET') . "\n\n";
}

echo "If Hollatag is the only provider you want to use, we need to:\n";
echo "1. Verify the credentials work on https://sms.hollatags.com\n";
echo "2. Check if the account has sufficient balance\n";
echo "3. Contact Hollatag support if credentials are correct but API returns error_user\n";
