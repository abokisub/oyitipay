<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiersAndLimitsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Disable strict mode for this session to handle large rows
        DB::statement('SET SESSION innodb_strict_mode=0');

        // Force DYNAMIC row format
        DB::statement('ALTER TABLE user ROW_FORMAT=DYNAMIC');

        // Aggressively convert large varchars to TEXT to save row space
        DB::statement('ALTER TABLE user MODIFY opay TEXT');
        DB::statement('ALTER TABLE user MODIFY dob TEXT');
        DB::statement('ALTER TABLE user MODIFY nin TEXT');
        DB::statement('ALTER TABLE user MODIFY occupation TEXT');
        DB::statement('ALTER TABLE user MODIFY marital_status TEXT');
        DB::statement('ALTER TABLE user MODIFY religion TEXT');
        DB::statement('ALTER TABLE user MODIFY app_token TEXT');
        DB::statement('ALTER TABLE user MODIFY paystack_account TEXT');
        DB::statement('ALTER TABLE user MODIFY paystack_bank TEXT');
        DB::statement('ALTER TABLE user MODIFY address TEXT');
        DB::statement('ALTER TABLE user MODIFY city TEXT');
        DB::statement('ALTER TABLE user MODIFY state TEXT');
        DB::statement('ALTER TABLE user MODIFY about TEXT');
        DB::statement('ALTER TABLE user MODIFY webhook TEXT');
        DB::statement('ALTER TABLE user MODIFY reason TEXT');
        DB::statement('ALTER TABLE user MODIFY id_card_path TEXT');
        DB::statement('ALTER TABLE user MODIFY utility_bill_path TEXT');

        Schema::table('user', function (Blueprint $table) {
            $table->string('kyc_tier', 20)->default('tier_0')->after('kyc'); // tier_0, tier_1, tier_2, tier_3
            $table->decimal('single_limit', 15, 2)->default(3000)->after('kyc_tier');
            $table->decimal('daily_limit', 15, 2)->default(10000)->after('single_limit');
            $table->decimal('daily_used', 15, 2)->default(0)->after('daily_limit');
            $table->date('daily_used_date')->nullable()->after('daily_used');
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
            $table->dropColumn(['kyc_tier', 'single_limit', 'daily_limit', 'daily_used', 'daily_used_date']);
        });
    }
}
