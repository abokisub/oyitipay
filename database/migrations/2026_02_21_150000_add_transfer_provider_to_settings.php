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
        // Check if column exists before adding
        if (!Schema::hasColumn('settings', 'transfer_provider')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('transfer_provider', 50)->default('pointwave')->after('transfer_charge_cap');
            });
            
            // Set default to pointwave for existing records
            DB::table('settings')->update(['transfer_provider' => 'pointwave']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'transfer_provider')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('transfer_provider');
            });
        }
    }
};
