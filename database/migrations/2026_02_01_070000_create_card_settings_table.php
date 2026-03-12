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
        if (!Schema::hasTable('card_settings')) {

            Schema::create('card_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('ngn_creation_fee', 10, 2)->default(500.00);
                $table->decimal('usd_creation_fee', 10, 2)->default(3.00);
                $table->decimal('funding_fee_percent', 5, 2)->default(1.00);
                $table->decimal('ngn_rate', 10, 2)->default(1600.00); // NGN to USD Rate
                $table->timestamps();
            });

            // Insert default
            DB::table('card_settings')->insert([
                'ngn_creation_fee' => 500.00,
                'usd_creation_fee' => 3.00,
                'funding_fee_percent' => 1.5,
                'ngn_rate' => 1650.00,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_settings');
    }
};
