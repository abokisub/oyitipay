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
        Schema::create('adex_api', function (Blueprint $table) {
            $table->id();
            $table->string('adex1_username')->nullable();
            $table->string('adex1_password')->nullable();
            $table->string('adex2_username')->nullable();
            $table->string('adex2_password')->nullable();
            $table->string('adex3_username')->nullable();
            $table->string('adex3_password')->nullable();
            $table->string('adex4_username')->nullable();
            $table->string('adex4_password')->nullable();
            $table->string('adex5_username')->nullable();
            $table->string('adex5_password')->nullable();
        });

        // Initial seed
        DB::table('adex_api')->insert(['id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adex_api');
    }
};
