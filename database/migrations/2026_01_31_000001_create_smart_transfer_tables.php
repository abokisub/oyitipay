<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSmartTransferTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create 'banks' table for central code mapping
        if (!Schema::hasTable('banks')) {
            Schema::create('banks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable(); // Standard/CBN Code
                $table->string('paystack_code')->nullable();
                $table->string('monnify_code')->nullable();
                $table->string('xixapay_code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create 'transfer_providers' table for router config
        if (!Schema::hasTable('transfer_providers')) {
            Schema::create('transfer_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Paystack, Monnify, Xixapay
                $table->string('slug')->unique(); // paystack, monnify, xixapay
                $table->integer('priority')->default(1);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_locked')->default(false); // Admin lock
                $table->integer('timeout_seconds')->default(30);
                $table->decimal('min_amount', 15, 2)->default(0);
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->decimal('daily_limit', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        // 3. Create 'transfers' table
        if (!Schema::hasTable('transfers')) {
            Schema::create('transfers', function (Blueprint $table) {
                $table->id();
                $table->string('user_id')->nullable(); // Linking to user table (assuming string or bigInt)
                $table->string('reference')->unique();
                $table->decimal('amount', 15, 2);
                $table->decimal('charge', 15, 2)->default(0);
                $table->string('bank_code');
                $table->string('account_number');
                $table->string('account_name')->nullable();
                $table->string('narration')->nullable();

                $table->string('provider_used')->nullable(); // paystack, monnify
                $table->string('provider_reference')->nullable();

                $table->string('status')->default('pending'); // pending, success, failed
                $table->text('last_error')->nullable();
                $table->integer('attempts')->default(0);

                $table->timestamps();
            });
        }

        // 4. Update 'settings' table - ADD COLUMNS IF NOT EXIST
        // We use Schema::hasColumn checks just in case
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'transfer_lock_all')) {
                $table->boolean('transfer_lock_all')->default(false);
            }
            if (!Schema::hasColumn('settings', 'transfer_charge_type')) {
                $table->string('transfer_charge_type')->default('FLAT'); // FLAT or PERCENT
            }
            if (!Schema::hasColumn('settings', 'transfer_charge_value')) {
                $table->decimal('transfer_charge_value', 10, 2)->default(10);
            }
            if (!Schema::hasColumn('settings', 'transfer_charge_cap')) {
                $table->decimal('transfer_charge_cap', 10, 2)->nullable(); // Max charge if Percent
            }
        });

        // Seed Default Providers
        if (Schema::hasTable('transfer_providers') && Schema::hasColumn('transfer_providers', 'is_active')) {
            DB::table('transfer_providers')->insertOrIgnore([
                ['name' => 'Xixapay', 'slug' => 'xixapay', 'priority' => 1, 'is_active' => true],
                ['name' => 'Monnify', 'slug' => 'monnify', 'priority' => 2, 'is_active' => true],
                ['name' => 'Paystack', 'slug' => 'paystack', 'priority' => 3, 'is_active' => true],
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
        Schema::dropIfExists('banks');
        Schema::dropIfExists('transfer_providers');
        Schema::dropIfExists('transfers');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['transfer_lock_all', 'transfer_charge_type', 'transfer_charge_value', 'transfer_charge_cap']);
        });
    }
}
