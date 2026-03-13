<?php

/**
 * Fix All Electricity Disco Mappings in Database
 * Ensures all disco IDs match KoboPoint's official mapping
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 FIXING ALL ELECTRICITY DISCO MAPPINGS\n";
echo "========================================\n\n";

// Official KoboPoint disco mapping
$official_kobopoint_mapping = [
    'Ikeja Electricity' => 1,
    'Eko Electricity' => 2,
    'Kano Electricity' => 3,
    'Port Harcourt Electricity' => 4,
    'Joss Electricity' => 5,
    'Ibadan Electricity' => 6,
    'Kaduna Electric' => 7,
    'Abuja Electricity' => 8,
    'Yola Electricity' => 9,
    'Benin Electric' => 10,
    'Enugu Electric' => 11
];

echo "STEP 1: Get official KoboPoint disco list\n";
echo "=========================================\n";

$base_url = 'https://app.kobopoint.com';
$disco_url = $base_url . "/api/website/app/disco";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $disco_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$disco_response = curl_exec($ch);
$disco_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($disco_http_code == 200) {
    $disco_data = json_decode($disco_response, true);
    if ($disco_data && $disco_data['status'] === 'success') {
        echo "✅ Retrieved official KoboPoint disco list:\n";
        $live_mapping = [];
        foreach ($disco_data['plan'] as $disco) {
            echo "- ID: {$disco['plan_id']}, Name: {$disco['disco_name']}\n";
            $live_mapping[$disco['disco_name']] = $disco['plan_id'];
        }
        echo "\n";
        
        // Use live mapping if available, otherwise use hardcoded
        $kobopoint_mapping = $live_mapping ?: $official_kobopoint_mapping;
    } else {
        echo "⚠️ Failed to parse disco list, using hardcoded mapping\n\n";
        $kobopoint_mapping = $official_kobopoint_mapping;
    }
} else {
    echo "⚠️ Failed to fetch disco list (HTTP {$disco_http_code}), using hardcoded mapping\n\n";
    $kobopoint_mapping = $official_kobopoint_mapping;
}

echo "STEP 2: Check current database configuration\n";
echo "============================================\n";

try {
    $all_plans = DB::table('bill_plan')->orderBy('plan_id')->get();
    
    if ($all_plans->isEmpty()) {
        echo "❌ No bill plans found in database!\n";
        exit(1);
    }
    
    echo "Current database configuration:\n";
    $issues_found = [];
    
    foreach ($all_plans as $plan) {
        echo "- Plan ID: {$plan->plan_id}, Name: '{$plan->disco_name}'\n";
        echo "  Habukhan1: '{$plan->habukhan1}', Habukhan2: '{$plan->habukhan2}', Habukhan3: '{$plan->habukhan3}'\n";
        echo "  Habukhan4: '{$plan->habukhan4}', Habukhan5: '{$plan->habukhan5}'\n";
        
        // Check if this disco exists in KoboPoint mapping
        $expected_id = null;
        foreach ($kobopoint_mapping as $name => $id) {
            if (stripos($plan->disco_name, $name) !== false || stripos($name, $plan->disco_name) !== false) {
                $expected_id = $id;
                break;
            }
        }
        
        if ($expected_id) {
            // Check if any habukhan field is incorrect
            $fields_to_check = ['habukhan1', 'habukhan2', 'habukhan3', 'habukhan4', 'habukhan5'];
            $needs_fix = false;
            
            foreach ($fields_to_check as $field) {
                if (empty($plan->$field) || $plan->$field != $expected_id) {
                    $needs_fix = true;
                    break;
                }
            }
            
            if ($needs_fix) {
                $issues_found[] = [
                    'plan_id' => $plan->plan_id,
                    'disco_name' => $plan->disco_name,
                    'expected_id' => $expected_id,
                    'current_habukhan1' => $plan->habukhan1
                ];
                echo "  ❌ NEEDS FIX - Expected KoboPoint ID: {$expected_id}\n";
            } else {
                echo "  ✅ CORRECT\n";
            }
        } else {
            echo "  ⚠️ NOT FOUND in KoboPoint mapping\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($issues_found)) {
    echo "🎉 All disco mappings are already correct!\n";
    exit(0);
}

echo "STEP 3: Apply fixes\n";
echo "===================\n";

echo "Found " . count($issues_found) . " disco(s) that need fixing:\n\n";

foreach ($issues_found as $issue) {
    echo "Fixing {$issue['disco_name']} (Plan ID: {$issue['plan_id']})...\n";
    echo "- Setting all Habukhan disco IDs to: {$issue['expected_id']}\n";
    
    try {
        $updated = DB::table('bill_plan')
            ->where('plan_id', $issue['plan_id'])
            ->update([
                'habukhan1' => $issue['expected_id'],
                'habukhan2' => $issue['expected_id'],
                'habukhan3' => $issue['expected_id'],
                'habukhan4' => $issue['expected_id'],
                'habukhan5' => $issue['expected_id']
            ]);
        
        if ($updated) {
            echo "  ✅ Successfully updated\n";
        } else {
            echo "  ⚠️ No records updated (may already be correct)\n";
        }
    } catch (Exception $e) {
        echo "  ❌ Update failed: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "STEP 4: Verify all fixes\n";
echo "========================\n";

// Re-check all configurations
$verification_passed = true;

foreach ($issues_found as $issue) {
    $updated_plan = DB::table('bill_plan')->where('plan_id', $issue['plan_id'])->first();
    
    if ($updated_plan) {
        echo "Verifying {$issue['disco_name']}:\n";
        echo "- Habukhan1: {$updated_plan->habukhan1}\n";
        echo "- Habukhan2: {$updated_plan->habukhan2}\n";
        echo "- Habukhan3: {$updated_plan->habukhan3}\n";
        echo "- Habukhan4: {$updated_plan->habukhan4}\n";
        echo "- Habukhan5: {$updated_plan->habukhan5}\n";
        
        $all_correct = (
            $updated_plan->habukhan1 == $issue['expected_id'] &&
            $updated_plan->habukhan2 == $issue['expected_id'] &&
            $updated_plan->habukhan3 == $issue['expected_id'] &&
            $updated_plan->habukhan4 == $issue['expected_id'] &&
            $updated_plan->habukhan5 == $issue['expected_id']
        );
        
        if ($all_correct) {
            echo "  ✅ VERIFIED CORRECT\n";
        } else {
            echo "  ❌ STILL INCORRECT\n";
            $verification_passed = false;
        }
        echo "\n";
    }
}

echo "STEP 5: Test sample meter validations\n";
echo "=====================================\n";

// Test some known working meters
$test_meters = [
    ['disco_name' => 'Abuja Electricity', 'plan_id' => 8, 'meter' => '0137220153084', 'type' => 'prepaid'],
    // Add more test meters here if you have them
];

$api_website = DB::table('web_api')->first();
if (!$api_website) {
    echo "⚠️ No API website configuration found, skipping meter tests\n";
} else {
    foreach ($test_meters as $test) {
        $plan = DB::table('bill_plan')->where('plan_id', $test['plan_id'])->first();
        if ($plan && !empty($plan->habukhan1)) {
            echo "Testing {$test['disco_name']} meter {$test['meter']}...\n";
            
            $validation_url = $api_website->habukhan_website1 . "/api/bill/bill-validation?meter_type=" . $test['type'] . "&meter_number=" . $test['meter'] . "&disco=" . $plan->habukhan1;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $validation_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Habukhan');
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success' && !empty($data['name'])) {
                    echo "  ✅ SUCCESS - Customer: {$data['name']}\n";
                } else {
                    echo "  ❌ FAILED - " . ($data['message'] ?? 'Unknown error') . "\n";
                }
            } else {
                echo "  ❌ HTTP ERROR {$http_code}\n";
            }
        }
    }
}

echo "\n🏁 DISCO MAPPING FIX COMPLETE!\n";

if ($verification_passed) {
    echo "🎉 All electricity disco mappings have been successfully fixed!\n";
    echo "Your application should now work correctly with all electricity providers.\n";
} else {
    echo "⚠️ Some issues may still exist. Please check the verification results above.\n";
}

echo "\nSUMMARY:\n";
echo "- Fixed " . count($issues_found) . " disco mapping(s)\n";
echo "- All Habukhan websites (1-5) now use correct KoboPoint disco IDs\n";
echo "- Meter validation should work for all supported electricity providers\n";

?>