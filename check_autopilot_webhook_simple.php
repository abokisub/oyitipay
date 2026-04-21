<?php
/**
 * Simple Autopilot Webhook Checker
 * Run this on your cPanel via SSH or Terminal
 */

echo "\n🔍 AUTOPILOT WEBHOOK QUICK CHECK\n";
echo "================================\n\n";

// 1. Check if webhook URL is accessible
echo "1. Testing webhook URL accessibility...\n";
$url = "https://app.oyitipay.com/api/autopilot/webhook/secure";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 || $httpCode == 405) {
    echo "   ✅ URL is accessible (HTTP $httpCode)\n";
} elseif ($httpCode == 404) {
    echo "   ❌ URL returns 404 - Route not configured!\n";
} else {
    echo "   ⚠️  URL returns HTTP $httpCode\n";
}

// 2. Check Laravel log for webhook activity
echo "\n2. Checking recent webhook logs...\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    $cmd = "tail -n 50 " . escapeshellarg($logFile) . " | grep -i 'autopilot'";
    $output = shell_exec($cmd);
    
    if ($output) {
        echo "   ✅ Found Autopilot webhook activity:\n";
        echo "   " . str_replace("\n", "\n   ", trim($output)) . "\n";
    } else {
        echo "   ⚠️  No Autopilot webhook logs found in last 50 lines\n";
        echo "   This means webhook hasn't been called recently\n";
    }
} else {
    echo "   ❌ Log file not found: $logFile\n";
}

// 3. Send test webhook
echo "\n3. Sending test webhook request...\n";

$testPayload = [
    'status' => 'success',
    'data' => [
        'reference' => 'TEST_VERIFY_' . time(),
        'product' => 'data',
        'amount' => 100
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Error: $error\n";
} else {
    echo "   HTTP Code: $httpCode\n";
    echo "   Response: $response\n";
    
    if ($httpCode == 200) {
        echo "   ✅ Webhook is working!\n";
    } else {
        echo "   ⚠️  Unexpected response\n";
    }
}

echo "\n================================\n";
echo "✅ Check complete!\n\n";

echo "Next steps:\n";
echo "1. Check your Autopilot dashboard webhook logs\n";
echo "2. Make a test transaction and monitor logs\n";
echo "3. Run: tail -f storage/logs/laravel.log | grep Autopilot\n\n";
