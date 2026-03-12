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
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('typing_status')->nullable()->after('updated_at'); // 'user', 'agent', 'bot'
            $table->string('typing_agent_name')->nullable()->after('typing_status');
            $table->timestamp('typing_started_at')->nullable()->after('typing_agent_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['typing_status', 'typing_agent_name', 'typing_started_at']);
        });
    }
};