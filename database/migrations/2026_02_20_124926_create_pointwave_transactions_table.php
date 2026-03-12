<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePointwaveTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pointwave_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->enum('type', ['deposit', 'transfer', 'withdrawal']);
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'successful', 'failed', 'refunded'])->default('pending');
            $table->string('reference', 100);
            $table->string('pointwave_transaction_id', 100)->nullable();
            $table->string('pointwave_customer_id', 100)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->text('narration')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique('reference');
            
            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('reference');
            $table->index('pointwave_transaction_id');
            $table->index('created_at');
            $table->index(['user_id', 'type', 'status']);
        });
        
        // Set charset and collation to match user table
        DB::statement('ALTER TABLE pointwave_transactions CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pointwave_transactions');
    }
}
