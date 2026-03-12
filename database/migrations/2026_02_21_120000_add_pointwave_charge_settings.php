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
        Schema::table('settings', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('settings', 'pointwave_charge_type')) {
                $table->string('pointwave_charge_type', 20)->default('FLAT')->after('pointwave_business_id')
                    ->comment('FLAT or PERCENTAGE');
            }
            
            if (!Schema::hasColumn('settings', 'pointwave_charge_value')) {
                $table->decimal('pointwave_charge_value', 10, 2)->default(0.00)->after('pointwave_charge_type')
                    ->comment('Charge amount (flat) or percentage value');
            }
            
            if (!Schema::hasColumn('settings', 'pointwave_charge_cap')) {
                $table->decimal('pointwave_charge_cap', 10, 2)->default(0.00)->after('pointwave_charge_value')
                    ->comment('Maximum charge cap (0 = no cap)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'pointwave_charge_type')) {
                $table->dropColumn('pointwave_charge_type');
            }
            if (Schema::hasColumn('settings', 'pointwave_charge_value')) {
                $table->dropColumn('pointwave_charge_value');
            }
            if (Schema::hasColumn('settings', 'pointwave_charge_cap')) {
                $table->dropColumn('pointwave_charge_cap');
            }
        });
    }
};
