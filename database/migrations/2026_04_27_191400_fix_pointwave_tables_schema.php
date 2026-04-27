<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix pointwave_kyc table
        if (Schema::hasTable('pointwave_kyc')) {
            // Check if 'status' column exists and needs renaming to 'kyc_status'
            // Using raw SQL to avoid Doctrine dependency in Laravel 8
            if (Schema::hasColumn('pointwave_kyc', 'status') && !Schema::hasColumn('pointwave_kyc', 'kyc_status')) {
                try {
                    DB::statement("ALTER TABLE pointwave_kyc CHANGE COLUMN status kyc_status ENUM('not_submitted', 'pending', 'verified', 'rejected') DEFAULT 'not_submitted'");
                } catch (\Exception $e) {
                    \Log::error("Migration Error: Could not rename status to kyc_status: " . $e->getMessage());
                }
            } else if (Schema::hasColumn('pointwave_kyc', 'kyc_status')) {
                // Just ensure the enum values are correct if it already exists
                try {
                    DB::statement("ALTER TABLE pointwave_kyc MODIFY COLUMN kyc_status ENUM('not_submitted', 'pending', 'verified', 'rejected') DEFAULT 'not_submitted'");
                } catch (\Exception $e) {
                    \Log::warning("Migration Warning: Could not modify kyc_status enum: " . $e->getMessage());
                }
            }
            
            // Add other columns if missing
            Schema::table('pointwave_kyc', function (Blueprint $table) {
                if (!Schema::hasColumn('pointwave_kyc', 'transaction_limit')) {
                    $table->decimal('transaction_limit', 15, 2)->default(50000.00)->after('daily_limit');
                }
                
                if (!Schema::hasColumn('pointwave_kyc', 'bvn')) {
                    $table->string('bvn')->nullable()->after('user_id');
                }
                
                if (!Schema::hasColumn('pointwave_kyc', 'nin')) {
                    $table->string('nin')->nullable()->after('bvn');
                }
            });
        }

        // 2. Fix pointwave_transactions table
        if (Schema::hasTable('pointwave_transactions')) {
            Schema::table('pointwave_transactions', function (Blueprint $table) {
                // Add missing columns
                if (!Schema::hasColumn('pointwave_transactions', 'pointwave_transaction_id')) {
                    $table->string('pointwave_transaction_id', 100)->nullable()->after('reference');
                }
                
                if (!Schema::hasColumn('pointwave_transactions', 'pointwave_customer_id')) {
                    $table->string('pointwave_customer_id', 100)->nullable()->after('pointwave_transaction_id');
                }
                
                if (!Schema::hasColumn('pointwave_transactions', 'account_number')) {
                    $table->string('account_number', 20)->nullable()->after('pointwave_customer_id');
                }
                
                if (!Schema::hasColumn('pointwave_transactions', 'bank_code')) {
                    $table->string('bank_code', 10)->nullable()->after('account_number');
                }
                
                if (!Schema::hasColumn('pointwave_transactions', 'account_name')) {
                    $table->string('account_name', 255)->nullable()->after('bank_code');
                }
                
                if (!Schema::hasColumn('pointwave_transactions', 'narration')) {
                    $table->text('narration')->nullable()->after('account_name');
                }
            });
            
            // Fix enum values for status and type using raw SQL
            try {
                // Combine status enums
                DB::statement("ALTER TABLE pointwave_transactions MODIFY COLUMN status ENUM('pending', 'successful', 'completed', 'failed', 'refunded') DEFAULT 'pending'");
                
                // Combine type enums
                DB::statement("ALTER TABLE pointwave_transactions MODIFY COLUMN type ENUM('deposit', 'transfer', 'withdrawal', 'fee')");
            } catch (\Exception $e) {
                \Log::warning("Migration Warning: Could not modify pointwave_transactions enums: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed
    }
};
