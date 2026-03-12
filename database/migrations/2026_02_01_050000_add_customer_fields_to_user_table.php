<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'customer_id')) {
                if (!Schema::hasColumn('user', 'customer_id')) {
                    $table->string('customer_id')->nullable()->after('kyc');
                }
            }
            if (!Schema::hasColumn('user', 'customer_data')) {
                if (!Schema::hasColumn('user', 'customer_data')) {
                    $table->longText('customer_data')->nullable()->after('customer_id');
                }
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
        Schema::table('user', function (Blueprint $table) {
            if (Schema::hasColumn('user', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('user', 'customer_data')) {
                $table->dropColumn('customer_data');
            }
        });
    }
};
