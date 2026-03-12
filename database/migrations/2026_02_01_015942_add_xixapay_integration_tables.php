<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. KYC Table
        if (!Schema::hasTable('user_kyc')) {
            Schema::create('user_kyc', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->string('id_type'); // 'bvn', 'nin'
                $table->string('id_number'); // hashed + masked
                $table->longText('full_response_json')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->string('provider')->default('xixapay');
                $table->string('status')->default('verified'); // 'verified', 'failed'
                $table->timestamps();

                // Unique Constraint: Prevent duplicate identity usage
                $table->unique(['id_type', 'id_number']);
            });
        }

        // 2. Virtual Cards Table
        if (!Schema::hasTable('virtual_cards')) {
            Schema::create('virtual_cards', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->string('provider')->default('xixapay');
                $table->string('card_id');
                $table->string('card_type'); // 'NGN', 'USD'
                $table->string('status')->default('active'); // 'active', 'frozen', 'blocked', 'terminated'
                $table->longText('full_response_json')->nullable();
                $table->timestamps();

                // Logic Constraint: Enforce 1 active card per type per user
                // We will handle the "status != terminated" check in application logic 
                // or a partial index if DB supports it, but unique(user_id, card_type) is a good baseline
            });
        }

        // 3. Card Transactions Table
        if (!Schema::hasTable('card_transactions')) {
            Schema::create('card_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('card_id');
                $table->string('xixapay_transaction_id');
                $table->decimal('amount', 20, 2);
                $table->string('currency');
                $table->string('status');
                $table->string('merchant_name')->nullable();
                $table->longText('raw_webhook_json')->nullable();
                $table->timestamps();
            });
        }

        // 4. System Locks Table
        if (!Schema::hasTable('system_locks')) {
            Schema::create('system_locks', function (Blueprint $table) {
                $table->id();
                $table->string('feature_key')->unique(); // 'kyc', 'card_ngn', 'card_usd'
                $table->boolean('is_locked')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_kyc');
        Schema::dropIfExists('virtual_cards');
        Schema::dropIfExists('card_transactions');
        Schema::dropIfExists('system_locks');
    }
};
