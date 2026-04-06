<?php
// Load secret from .env
$env = file_get_contents(__DIR__ . '/.env');
preg_match('/XIXAPAY_SECRET_KEY=(.+)/', $env, $m);
$secret = trim($m[1] ?? '');

if (!$secret) {
    die("ERROR: XIXAPAY_SECRET_KEY not found in .env\n");
}

// Use a real user email from your DB
$email = 'adexplug@gmail.com'; // <-- change to a real user email

$payload = json_encode([
    'notification_status' => 'payment_successful',
    'transaction_id' => 'TEST_' . time(),
    'amount_paid' => 100,
    'settlement_amount' => 99.5,
    'settlement_fee' => 0.5,
    'transaction_status' => 'success',
    'sender' => ['name' => 'TEST SENDER', 'account_number' => '****1234', 'bank' => 'TEST BANK'],
    'receiver' => ['name' => 'test', 'account_number' => '123456', 'bank' => 'PalmPay'],
    'customer' => ['name' => 'Test User', 'email' => $email, 'phone' => null, 'customer_id' => 'xxx'],
    'description' => 'Test payment',
    'timestamp' => date('c'),
]);

$sig = hash_hmac('sha256', $payload, $secret);

echo "Secret prefix: " . substr($secret, 0, 10) . "...\n";
echo "Signature: $sig\n";
echo "Payload: $payload\n\n";

$ch = curl_init('https://app.oyitipay.com/api/xixapay_webhook/secure/callback/pay/habukhan/0001');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'xixapay: ' . $sig,
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $code\n";
echo "Response: $response\n";
