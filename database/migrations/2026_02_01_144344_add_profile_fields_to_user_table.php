<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'dob')) {
                $table->string('dob')->nullable();
            }
            if (!Schema::hasColumn('user', 'bvn')) {
                $table->string('bvn')->nullable();
            }
            if (!Schema::hasColumn('user', 'nin')) {
                $table->string('nin')->nullable();
            }
            if (!Schema::hasColumn('user', 'next_of_kin')) {
                $table->json('next_of_kin')->nullable();
            }
            if (!Schema::hasColumn('user', 'occupation')) {
                $table->string('occupation')->nullable();
            }
            if (!Schema::hasColumn('user', 'marital_status')) {
                $table->string('marital_status')->nullable();
            }
            if (!Schema::hasColumn('user', 'religion')) {
                $table->string('religion')->nullable();
            }
            if (!Schema::hasColumn('user', 'xixapay_kyc_data')) {
                $table->json('xixapay_kyc_data')->nullable(); // Stores full Xixapay customer response
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
            //
        });
    }
}
