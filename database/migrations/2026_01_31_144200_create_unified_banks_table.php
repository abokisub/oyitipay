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
        Schema::create('unified_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // URL-friendly identifier (e.g., access-bank)
            $table->string('code')->index(); // Generic/CBN Code (e.g., 058)
            $table->string('paystack_code')->nullable();
            $table->string('xixapay_code')->nullable();
            $table->string('monnify_code')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Optional: Index for searches
            $table->index('paystack_code');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_banks');
    }
};
