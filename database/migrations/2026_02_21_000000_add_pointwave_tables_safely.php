<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPointwaveTablesSafely extends Migration
{
    /**
     * Run the migrations - SAFE VERSION
     * Only creates tables if they don't exist
     * Only adds columns if they don't exist
     *
     * @return void
     */
    public function up()
    {
        // Create pointwave_customers table (ONLY if it doesn't exist)
        if (!Schema::hasTable('pointwave_customers')) {
            Schema::create('pointwave_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->string('customer_id')->unique();
                $table->string('email');
                $table->string('first_name');
                $table->string('last_name');
                $table->string('phone_number');
                $table->string('bvn')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('customer_id');
            });
        }

        // Create pointwave_virtual_accounts table (ONLY if it doesn't exist)
        if (!Schema::hasTable('pointwave_virtual_accounts')) {
            Schema::create('pointwave_virtual_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('customer_id');
                $table->string('account_number')->unique();
                $table->string('account_name');
                $table->string('bank_name');
                $table->string('bank_code');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->string('external_reference')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('account_number');
                $table->index('customer_id');
            });
        }

        // Create pointwave_transactions table (ONLY if it doesn't exist)
        if (!Schema::hasTable('pointwave_transactions')) {
            Schema::create('pointwave_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('reference')->unique();
                $table->enum('type', ['deposit', 'transfer', 'fee']);
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 15, 2)->default(0);
                $table->string('currency', 3)->default('NGN');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('external_reference')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('reference');
                $table->index('status');
                $table->index('type');
            });
        }

        // Create pointwave_kyc table (ONLY if it doesn't exist)
        if (!Schema::hasTable('pointwave_kyc')) {
            Schema::create('pointwave_kyc', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->unique();
                $table->string('bvn')->nullable();
                $table->string('nin')->nullable();
                $table->enum('tier', ['tier1', 'tier2', 'tier3'])->default('tier1');
                $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->decimal('daily_limit', 15, 2)->default(50000);
                $table->decimal('transaction_limit', 15, 2)->default(50000);
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        // Add PointWave columns to settings table (ONLY if they don't exist)
        if (Schema::hasTable('settings')) {
            if (!Schema::hasColumn('settings', 'pointwave_enabled')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->boolean('pointwave_enabled')->default(false)->after('id');
                });
            }
            
            if (!Schema::hasColumn('settings', 'pointwave_api_key')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('pointwave_api_key')->nullable()->after('pointwave_enabled');
                });
            }
            
            if (!Schema::hasColumn('settings', 'pointwave_secret_key')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('pointwave_secret_key')->nullable()->after('pointwave_api_key');
                });
            }
            
            if (!Schema::hasColumn('settings', 'pointwave_business_id')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('pointwave_business_id')->nullable()->after('pointwave_secret_key');
                });
            }
        }

        // Create card_settings table if it doesn't exist
        if (!Schema::hasTable('card_settings')) {
            Schema::create('card_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('ngn_card_fee', 10, 2)->default(0);
                $table->decimal('usd_card_fee', 10, 2)->default(0);
                $table->boolean('ngn_card_enabled')->default(true);
                $table->boolean('usd_card_enabled')->default(true);
                $table->timestamps();
            });
            
            // Insert default settings
            DB::table('card_settings')->insert([
                'id' => 1,
                'ngn_card_fee' => 0,
                'usd_card_fee' => 0,
                'ngn_card_enabled' => 1,
                'usd_card_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // SAFE: Only drop PointWave tables, never touch existing tables
        Schema::dropIfExists('pointwave_transactions');
        Schema::dropIfExists('pointwave_virtual_accounts');
        Schema::dropIfExists('pointwave_customers');
        Schema::dropIfExists('pointwave_kyc');
        
        // Remove PointWave columns from settings (if they exist)
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (Schema::hasColumn('settings', 'pointwave_business_id')) {
                    $table->dropColumn('pointwave_business_id');
                }
                if (Schema::hasColumn('settings', 'pointwave_secret_key')) {
                    $table->dropColumn('pointwave_secret_key');
                }
                if (Schema::hasColumn('settings', 'pointwave_api_key')) {
                    $table->dropColumn('pointwave_api_key');
                }
                if (Schema::hasColumn('settings', 'pointwave_enabled')) {
                    $table->dropColumn('pointwave_enabled');
                }
            });
        }
    }
}
