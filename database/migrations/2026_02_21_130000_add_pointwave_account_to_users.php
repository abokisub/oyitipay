<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointwaveAccountToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'pointwave_account_number')) {
                $table->string('pointwave_account_number', 20)->nullable()->after('palmpay');
            }
            if (!Schema::hasColumn('user', 'pointwave_account_name')) {
                $table->string('pointwave_account_name', 255)->nullable()->after('pointwave_account_number');
            }
            if (!Schema::hasColumn('user', 'pointwave_bank_name')) {
                $table->string('pointwave_bank_name', 100)->nullable()->after('pointwave_account_name');
            }
            if (!Schema::hasColumn('user', 'pointwave_customer_id')) {
                $table->string('pointwave_customer_id', 100)->nullable()->after('pointwave_bank_name');
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
            if (Schema::hasColumn('user', 'pointwave_account_number')) {
                $table->dropColumn('pointwave_account_number');
            }
            if (Schema::hasColumn('user', 'pointwave_account_name')) {
                $table->dropColumn('pointwave_account_name');
            }
            if (Schema::hasColumn('user', 'pointwave_bank_name')) {
                $table->dropColumn('pointwave_bank_name');
            }
            if (Schema::hasColumn('user', 'pointwave_customer_id')) {
                $table->dropColumn('pointwave_customer_id');
            }
        });
    }
}
