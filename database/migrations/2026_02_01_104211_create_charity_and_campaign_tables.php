<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('charities')) {
            Schema::create('charities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username')->unique();
                $table->string('category');
                $table->string('verification_status')->default('unverified');
                $table->string('bank_account')->nullable();
                $table->string('bank_name')->nullable();
                $table->decimal('pending_balance', 15, 2)->default(0.00);
                $table->decimal('available_balance', 15, 2)->default(0.00);
                $table->json('documents')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('charity_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('target_amount', 15, 2);
                $table->decimal('current_amount', 15, 2)->default(0.00);
                $table->dateTime('start_date');
                $table->dateTime('end_date');
                $table->string('status')->default('active'); // active, closed, emergency
                $table->string('payout_status')->default('pending'); // pending, released
                $table->timestamps();

                $table->foreign('charity_id')->references('id')->on('charities')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('donations')) {
            Schema::create('donations', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->unsignedBigInteger('campaign_id');
                $table->unsignedBigInteger('charity_id');
                $table->decimal('amount', 15, 2);
                $table->string('transid')->unique();
                $table->string('status')->default('confirmed');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
                $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
                $table->foreign('charity_id')->references('id')->on('charities')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('charities');
    }
};
