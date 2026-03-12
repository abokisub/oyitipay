<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureKycColumnsInUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'customer_id'))
                $table->string('customer_id')->nullable()->after('apikey');
            if (!Schema::hasColumn('user', 'dob'))
                $table->date('dob')->nullable()->after('customer_id');
            if (!Schema::hasColumn('user', 'address'))
                $table->text('address')->nullable()->after('dob');
            if (!Schema::hasColumn('user', 'city'))
                $table->string('city', 100)->nullable()->after('address');
            if (!Schema::hasColumn('user', 'state'))
                $table->string('state', 100)->nullable()->after('city');
            if (!Schema::hasColumn('user', 'postal_code'))
                $table->string('postal_code', 20)->nullable()->after('state');
            if (!Schema::hasColumn('user', 'id_card_path'))
                $table->string('id_card_path')->nullable()->after('postal_code');
            if (!Schema::hasColumn('user', 'utility_bill_path'))
                $table->string('utility_bill_path')->nullable()->after('id_card_path');
            if (!Schema::hasColumn('user', 'kyc_documents'))
                $table->json('kyc_documents')->nullable()->after('utility_bill_path');
            if (!Schema::hasColumn('user', 'kyc_status'))
                $table->enum('kyc_status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending')->after('kyc_documents');
            if (!Schema::hasColumn('user', 'kyc_submitted_at'))
                $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
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
