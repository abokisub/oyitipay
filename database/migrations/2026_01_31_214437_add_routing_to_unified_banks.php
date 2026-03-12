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
        Schema::table('unified_banks', function (Blueprint $table) {
            if (!Schema::hasColumn('unified_banks', 'primary_provider')) {
                $table->string('primary_provider')->default('paystack');
            }
            if (!Schema::hasColumn('unified_banks', 'secondary_provider')) {
                $table->string('secondary_provider')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unified_banks', function (Blueprint $table) {
            $table->dropColumn(['primary_provider', 'secondary_provider']);
        });
    }
};
