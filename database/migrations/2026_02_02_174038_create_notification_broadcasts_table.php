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
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('broadcast_id')->unique();
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->string('target_type'); // ALL, SMART, AWUF, API, SPECIAL, CUSTOM
            $table->string('target_username')->nullable(); // For CUSTOM type
            $table->integer('sent_count')->default(0);
            $table->timestamps();

            $table->index('broadcast_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_broadcasts');
    }
};
