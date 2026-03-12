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
        Schema::table('notif', function (Blueprint $table) {
            $table->string('broadcast_id')->nullable()->after('image_url');
            $table->index('broadcast_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notif', function (Blueprint $table) {
            $table->dropIndex(['broadcast_id']);
            $table->dropColumn('broadcast_id');
        });
    }
};
