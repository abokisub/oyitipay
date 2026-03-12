<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('card_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('card_settings', 'usd_failed_tx_fee')) {
                $table->decimal('usd_failed_tx_fee', 10, 2)->default(0.40);
            }
            if (!Schema::hasColumn('card_settings', 'ngn_funding_fee_percent')) {
                $table->decimal('ngn_funding_fee_percent', 5, 2)->default(2.00);
            }
            if (!Schema::hasColumn('card_settings', 'usd_funding_fee_percent')) {
                $table->decimal('usd_funding_fee_percent', 5, 2)->default(2.00);
            }
            if (!Schema::hasColumn('card_settings', 'ngn_failed_tx_fee')) {
                $table->decimal('ngn_failed_tx_fee', 10, 2)->default(0.00);
            }
        });
    }

    public function down(): void
    {
        Schema::table('card_settings', function (Blueprint $table) {
            $table->dropColumn(['usd_failed_tx_fee', 'ngn_funding_fee_percent', 'usd_funding_fee_percent', 'ngn_failed_tx_fee']);
        });
    }
};
