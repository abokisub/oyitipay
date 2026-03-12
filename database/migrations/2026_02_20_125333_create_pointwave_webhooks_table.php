<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointwaveWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pointwave_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->json('payload');
            $table->string('signature', 255);
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('event_type');
            $table->index('processed');
            $table->index('created_at');
            $table->index(['processed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pointwave_webhooks');
    }
}
