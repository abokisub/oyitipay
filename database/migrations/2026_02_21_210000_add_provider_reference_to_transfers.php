<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderReferenceToTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'provider_reference')) {
                $table->string('provider_reference')->nullable()->after('narration');
            }
            if (!Schema::hasColumn('transfers', 'provider_used')) {
                $table->string('provider_used')->nullable()->after('provider_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (Schema::hasColumn('transfers', 'provider_reference')) {
                $table->dropColumn('provider_reference');
            }
            if (Schema::hasColumn('transfers', 'provider_used')) {
                $table->dropColumn('provider_used');
            }
        });
    }
}
