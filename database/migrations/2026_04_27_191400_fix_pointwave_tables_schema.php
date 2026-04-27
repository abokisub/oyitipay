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
            Schema::table('pointwave_kyc', function (Blueprint $table) {
                // Rename status to kyc_status if status exists and kyc_status does not
                if (Schema::hasColumn('pointwave_kyc', 'status') && !Schema::hasColumn('pointwave_kyc', 'kyc_status')) {
                    $table->renameColumn('status', 'kyc_status');
                }
                
                // Add transaction_limit if missing
                if (!Schema::hasColumn('pointwave_kyc', 'transaction_limit')) {
                    $table->decimal('transaction_limit', 15, 2)->default(50000.00)->after('daily_limit');
                }
                
                // Add bvn if missing
                if (!Schema::hasColumn('pointwave_kyc', 'bvn')) {
                    $table->string('bvn')->nullable()->after('user_id');
                }
                
                // Add nin if missing
                if (!Schema::hasColumn('pointwave_kyc', 'nin')) {
                    $table->string('nin')->nullable()->after('bvn');
                }
                
                // Ensure kyc_status is an enum with correct values if it was renamed or already exists
                // Note: We use DB statement for enum modifications as Blueprint change() can be tricky with enums
            });
            
            // Fix enum values for kyc_status
            try {
                DB::statement("ALTER TABLE pointwave_kyc MODIFY COLUMN kyc_status ENUM('not_submitted', 'pending', 'verified', 'rejected') DEFAULT 'not_submitted'");
            } catch (\Exception $e) {
                \Log::warning("Could not modify kyc_status enum: " . $e->getMessage());
            }
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
            
            // Fix enum values for status and type
            try {
                // Combine status enums from both versions: successful (v1) and completed (v2)
                DB::statement("ALTER TABLE pointwave_transactions MODIFY COLUMN status ENUM('pending', 'successful', 'completed', 'failed', 'refunded') DEFAULT 'pending'");
                
                // Combine type enums: deposit, transfer, withdrawal (v1) and deposit, transfer, fee (v2)
                DB::statement("ALTER TABLE pointwave_transactions MODIFY COLUMN type ENUM('deposit', 'transfer', 'withdrawal', 'fee')");
            } catch (\Exception $e) {
                \Log::warning("Could not modify pointwave_transactions enums: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for schema fixes of this nature
    }
};
