<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Add missing columns that TransferPurchase controller uses
            if (!Schema::hasColumn('transfers', 'session_id')) {
                $table->string('session_id')->nullable()->after('provider_reference');
            }
            if (!Schema::hasColumn('transfers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('bank_code');
            }
            if (!Schema::hasColumn('transfers', 'date')) {
                $table->timestamp('date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('transfers', 'oldbal')) {
                $table->decimal('oldbal', 15, 2)->nullable()->after('charge');
            }
            if (!Schema::hasColumn('transfers', 'newbal')) {
                $table->decimal('newbal', 15, 2)->nullable()->after('oldbal');
            }
            if (!Schema::hasColumn('transfers', 'system')) {
                $table->string('system')->nullable()->after('status'); // APP, WEB, API
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'bank_name', 'date', 'oldbal', 'newbal', 'system']);
        });
    }
};
