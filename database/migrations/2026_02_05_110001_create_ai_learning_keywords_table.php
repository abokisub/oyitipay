<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiLearningKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_learning_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100)->unique();
            $table->text('response');
            $table->string('action', 50)->nullable(); // e.g., 'check_balance', 'escalate'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_learning_keywords');
    }
}
