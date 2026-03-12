<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name'); // Account Name or Wallet Name

            // External Transfer Fields
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->index(); // Serves as phone for internal too

            // Internal Transfer Fields
            $table->string('wallet_username')->nullable();

            $table->enum('type', ['internal', 'external'])->default('external');

            $table->timestamp('last_used_at')->useCurrent()->index(); // Critical for sorting recents
            $table->timestamps();

            // Prevent duplicates per user
            // user_id + account_number + bank_code (for external)
            // user_id + account_number (for internal - phone)
            // For simplicity, we can just index user_id and verify existence in controller/logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
