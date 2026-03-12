<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePaystackKeyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('paystack_key')) {
            Schema::create('paystack_key', function (Blueprint $table) {
                $table->id();
                $table->string('public')->nullable(); // Public Key
                $table->string('live')->nullable();   // Secret Key (mapped to $key->live in PaystackProvider)
                $table->timestamps();
            });

            // Insert placeholder to prevent "Trying to get property of non-object" errors (null keys handled by provider)
            DB::table('paystack_key')->insert([
                'public' => 'pk_test_placeholder',
                'live' => 'sk_test_placeholder',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paystack_key');
    }
}
