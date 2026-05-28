<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKycSelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kyc_sel', function (Blueprint $table) {
            $table->id();
            $table->string('kyc')->default('kobopoint');
            $table->timestamps();
        });

        // Insert default row for KYC
        DB::table('kyc_sel')->insert([
            'kyc' => 'kobopoint',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also add kobopoint to transfer providers
        DB::table('transfer_providers')->updateOrInsert(
            ['slug' => 'kobopoint'],
            [
                'name' => 'Kobopoint',
                'is_active' => 1,
                'is_locked' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kyc_sel');
        DB::table('transfer_providers')->where('slug', 'kobopoint')->delete();
    }
}
