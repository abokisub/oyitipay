<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePointwaveVirtualAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pointwave_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('customer_id', 100);
            $table->string('account_number', 20);
            $table->string('account_name', 255);
            $table->string('bank_name', 100);
            $table->string('bank_code', 10)->default('100033');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('external_reference', 100)->nullable();
            $table->timestamps();
            
            // Unique constraints
            $table->unique('user_id');
            $table->unique('customer_id');
            $table->unique('account_number');
            $table->unique('external_reference');
            
            // Indexes
            $table->index('user_id');
            $table->index('customer_id');
            $table->index('status');
        });
        
        // Set charset and collation to match user table
        DB::statement('ALTER TABLE pointwave_virtual_accounts CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pointwave_virtual_accounts');
    }
}
