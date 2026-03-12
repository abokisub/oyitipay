<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceSupportTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Dropping to fix 'Row size too large' issues and ensure clean state
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 32)->unique()->nullable();
            $table->integer('user_id');
            $table->text('subject')->nullable();
            $table->string('status', 20)->default('open'); // open, pending_agent, active, closed
            $table->string('priority', 20)->default('medium');
            $table->string('type', 20)->default('ai'); // ai, human
            $table->string('current_handler', 20)->default('ai'); // ai, agent
            $table->integer('assigned_agent_id')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('sender_type', 20); // user, agent, bot
            $table->integer('sender_id')->nullable();
            $table->text('message');
            $table->boolean('system_message')->default(false);
            $table->boolean('read_by_user')->default(false);
            $table->boolean('read_by_agent')->default(false);
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
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['ticket_code', 'current_handler', 'assigned_agent_id', 'closed_at']);
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['system_message', 'read_by_user', 'read_by_agent']);
        });
    }
}
