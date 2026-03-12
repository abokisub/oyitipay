<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unified_banks') && !Schema::hasColumn('unified_banks', 'pointwave_code')) {
            Schema::table('unified_banks', function (Blueprint $table) {
                $table->string('pointwave_code', 50)->nullable()->after('code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('unified_banks', 'pointwave_code')) {
            Schema::table('unified_banks', function (Blueprint $table) {
                $table->dropColumn('pointwave_code');
            });
        }
    }
};
