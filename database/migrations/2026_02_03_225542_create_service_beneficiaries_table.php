<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('service_beneficiaries'))
            return;

        Schema::create('service_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('service_type'); // airtime, data, electricity, tv, transfer_internal, transfer_external
            $table->string('identifier'); // Phone, Meter, SmartCard, Account
            $table->string('network_or_provider')->nullable(); // MTN, IKEDC, DSTV, Bank Name
            $table->string('name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'service_type', 'identifier'], 'user_service_identifier_unique');
            $table->index(['user_id', 'service_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_beneficiaries');
    }
}
