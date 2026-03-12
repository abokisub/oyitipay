<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('bank_code');
        });

        // Populate bank_name from unified_banks for existing records
        DB::statement("
            UPDATE transfers t
            LEFT JOIN unified_banks ub ON t.bank_code = ub.paystack_code
            SET t.bank_name = ub.name
            WHERE t.bank_name IS NULL AND ub.name IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('bank_name');
        });
    }
};