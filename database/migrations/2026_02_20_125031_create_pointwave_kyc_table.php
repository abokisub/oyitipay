<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePointwaveKycTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pointwave_kyc', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->enum('id_type', ['bvn', 'nin']);
            $table->text('id_number_encrypted');
            $table->enum('kyc_status', ['not_submitted', 'pending', 'verified', 'rejected'])->default('not_submitted');
            $table->enum('tier', ['tier_1', 'tier_3'])->default('tier_1');
            $table->decimal('daily_limit', 15, 2)->default(300000.00);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique('user_id');
            
            // Indexes
            $table->index('kyc_status');
            $table->index('tier');
        });
        
        // Set charset and collation to match user table
        DB::statement('ALTER TABLE pointwave_kyc CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pointwave_kyc');
    }
}
