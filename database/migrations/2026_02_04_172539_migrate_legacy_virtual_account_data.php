<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyVirtualAccountData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = Illuminate\Support\Facades\DB::table('user')->get();

        foreach ($users as $user) {
            // 1. Migrate Wema to paystack_account if paystack_account is empty
            if (!empty($user->wema) && empty($user->paystack_account)) {
                Illuminate\Support\Facades\DB::table('user')->where('id', $user->id)->update([
                    'paystack_account' => $user->wema,
                    'paystack_bank' => 'WEMA BANK'
                ]);
            }

            // 2. Migrate Moniepoint (sterlen) to user_bank table
            if (!empty($user->sterlen)) {
                $exists = Illuminate\Support\Facades\DB::table('user_bank')
                    ->where('username', $user->username)
                    ->where('bank', 'MONIEPOINT')
                    ->exists();

                if (!$exists) {
                    Illuminate\Support\Facades\DB::table('user_bank')->insert([
                        'username' => $user->username,
                        'bank' => 'MONIEPOINT',
                        'account_number' => $user->sterlen,
                        'bank_name' => 'KOBOPOINT - ' . strtoupper($user->username),
                        'bank_code' => '50515',
                        'date' => Carbon\Carbon::now("Africa/Lagos")->toDateTimeString(),
                    ]);
                }
            }

            // Note: 'fed' is retired as redundant but we could preserve it in user_bank if needed.
            // Following user's "clean 4 provider" request, we skip migrating 'fed' unless asked.
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reversing data migration is complex; usually we leave the data in standardized locations.
    }
}
