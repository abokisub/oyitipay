<?php

/**
 * Fix Missing User Table Columns
 * Adds missing columns that are referenced in the code but don't exist in database
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 FIXING MISSING USER TABLE COLUMNS\n";
echo "====================================\n\n";

echo "STEP 1: Check current user table structure\n";
echo "==========================================\n";

try {
    // Get current table columns
    $columns = Schema::getColumnListing('user');
    
    echo "Current user table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column}\n";
    }
    echo "\n";
    
    // Define the columns that are referenced in the code but might be missing
    $expected_columns = [
        'sterlen' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Sterling Bank account"',
        'wema' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Wema Bank account"', 
        'kolomoni_mfb' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Kolomoni MFB account"',
        'fed' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Federal account"',
        'otp' => 'VARCHAR(10) NULL DEFAULT NULL COMMENT "One-time password"',
        'autofund' => 'VARCHAR(50) NULL DEFAULT NULL COMMENT "Auto funding status"',
        'paystack_account' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Paystack virtual account"',
        'paystack_bank' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "Paystack bank name"',
        'opay' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "OPay account"',
        'palmpay' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "PalmPay account"',
        'pointwave_account_number' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "PointWave account number"',
        'pointwave_bank_name' => 'VARCHAR(255) NULL DEFAULT NULL COMMENT "PointWave bank name"'
    ];
    
    $missing_columns = [];
    $existing_columns = [];
    
    foreach ($expected_columns as $column => $definition) {
        if (in_array($column, $columns)) {
            $existing_columns[] = $column;
        } else {
            $missing_columns[$column] = $definition;
        }
    }
    
    echo "STEP 2: Analysis\n";
    echo "===============\n";
    
    if (!empty($existing_columns)) {
        echo "✅ Existing columns that are working:\n";
        foreach ($existing_columns as $column) {
            echo "- {$column}\n";
        }
        echo "\n";
    }
    
    if (!empty($missing_columns)) {
        echo "❌ Missing columns that need to be added:\n";
        foreach ($missing_columns as $column => $definition) {
            echo "- {$column}\n";
        }
        echo "\n";
        
        echo "STEP 3: Add missing columns\n";
        echo "===========================\n";
        
        foreach ($missing_columns as $column => $definition) {
            echo "Adding column: {$column}...\n";
            
            try {
                $sql = "ALTER TABLE `user` ADD COLUMN `{$column}` {$definition}";
                DB::statement($sql);
                echo "  ✅ Successfully added {$column}\n";
            } catch (Exception $e) {
                echo "  ❌ Failed to add {$column}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nSTEP 4: Verify additions\n";
        echo "========================\n";
        
        // Re-check columns
        $new_columns = Schema::getColumnListing('user');
        $successfully_added = [];
        
        foreach ($missing_columns as $column => $definition) {
            if (in_array($column, $new_columns)) {
                $successfully_added[] = $column;
                echo "✅ {$column} - Successfully added\n";
            } else {
                echo "❌ {$column} - Still missing\n";
            }
        }
        
        if (count($successfully_added) === count($missing_columns)) {
            echo "\n🎉 All missing columns have been successfully added!\n";
        } else {
            echo "\n⚠️ Some columns are still missing. Check the errors above.\n";
        }
        
    } else {
        echo "🎉 All expected columns already exist in the user table!\n";
    }
    
    echo "\nSTEP 5: Test the fix\n";
    echo "====================\n";
    
    // Test if we can now update a user without errors
    echo "Testing user update functionality...\n";
    
    try {
        // Find a test user (not admin)
        $test_user = DB::table('user')->where('type', '!=', 'ADMIN')->first();
        
        if ($test_user) {
            echo "Found test user: {$test_user->username}\n";
            
            // Try to update with the previously problematic fields
            $update_data = [];
            foreach (array_keys($missing_columns) as $column) {
                if (in_array($column, $new_columns)) {
                    $update_data[$column] = null; // Set to null as test
                }
            }
            
            if (!empty($update_data)) {
                DB::table('user')->where('id', $test_user->id)->update($update_data);
                echo "✅ User update test successful!\n";
            } else {
                echo "ℹ️ No new columns to test\n";
            }
        } else {
            echo "ℹ️ No test user found, skipping update test\n";
        }
        
    } catch (Exception $e) {
        echo "❌ User update test failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🏁 USER TABLE COLUMN FIX COMPLETE!\n";
echo "The AdminController EditUser function should now work without database errors.\n";

?>