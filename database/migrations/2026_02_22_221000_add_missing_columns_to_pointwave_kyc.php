<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pointwave_kyc', function (Blueprint $table) {
            // Add transaction_limit column (code uses this instead of just daily_limit)
            if (!Schema::hasColumn('pointwave_kyc', 'transaction_limit')) {
                $table->decimal('transaction_limit', 15, 2)->default(50000.00)->after('daily_limit');
            }
            
            // Add bvn and nin columns for storing verification numbers
            if (!Schema::hasColumn('pointwave_kyc', 'bvn')) {
                $table->string('bvn')->nullable()->after('id_number_encrypted');
            }
            if (!Schema::hasColumn('pointwave_kyc', 'nin')) {
                $table->string('nin')->nullable()->after('bvn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pointwave_kyc', function (Blueprint $table) {
            $table->dropColumn(['transaction_limit', 'bvn', 'nin']);
        });
    }
};
