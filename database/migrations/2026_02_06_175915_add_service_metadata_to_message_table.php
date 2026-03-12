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
        Schema::table('message', function (Blueprint $table) {
            $table->string('service_type')->nullable()->after('role');
            $table->enum('transaction_channel', ['INTERNAL', 'EXTERNAL'])->default('EXTERNAL')->after('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'transaction_channel']);
        });
    }
};