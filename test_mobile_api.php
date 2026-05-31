<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Helper to find a user to test with
// You can change 'jibrinowutoshi@gmail.com' to any user email on your live server
$emailToTest = 'jibrinowutoshi@gmail.com'; 
$user = \App\Models\User::where('email', $emailToTest)->first();

if (!$user) {
    // Fallback if that email isn't found
    $user = \App\Models\User::first();
}

if (!$user) {
    die("No users found in the database to test with.\n");
}

echo "Testing with User: " . $user->email . " (ID: " . $user->id . ")\n";
echo "=========================================\n";
echo "TESTING NIN VERIFICATION (Mobile App Flow)\n";
echo "=========================================\n";

$requestNIN = \Illuminate\Http\Request::create('/api/user/kyc/update', 'POST', [
    'id_type' => 'nin', 
    'id_number' => '59595043595', 
]);

// Set up Auth properly for the API request
$requestNIN->headers->set('Authorization', 'Bearer ' . $user->apikey);
$requestNIN->setUserResolver(function() use ($user) { return $user; });

try {
    $controller = app(\App\Http\Controllers\APP\Auth::class);
    $response = $controller->updateKyc($requestNIN);
    echo "STATUS CODE: " . $response->getStatusCode() . "\n";
    echo "RESPONSE BODY:\n";
    echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION THROWN:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=========================================\n";
echo "TESTING BVN VERIFICATION (Mobile App Flow)\n";
echo "=========================================\n";

$requestBVN = \Illuminate\Http\Request::create('/api/user/kyc/update', 'POST', [
    'id_type' => 'bvn', 
    'id_number' => '22232758225', 
    'dob' => '1991-07-03',
    'verification_method' => 'dob',
    'verification_value' => '1991-07-03'
]);

$requestBVN->headers->set('Authorization', 'Bearer ' . $user->apikey);
$requestBVN->setUserResolver(function() use ($user) { return $user; });

try {
    $controller = app(\App\Http\Controllers\APP\Auth::class);
    $response = $controller->updateKyc($requestBVN);
    echo "STATUS CODE: " . $response->getStatusCode() . "\n";
    echo "RESPONSE BODY:\n";
    echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION THROWN:\n";
    echo $e->getMessage() . "\n";
}
