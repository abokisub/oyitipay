<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCampaignsTableV1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('campaigns', 'state')) {
                $table->string('state')->nullable()->after('image');
            }
            if (!Schema::hasColumn('campaigns', 'lga')) {
                $table->string('lga')->nullable()->after('state');
            }
            if (!Schema::hasColumn('campaigns', 'phone')) {
                $table->string('phone')->nullable()->after('lga');
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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['image', 'state', 'lga', 'phone']);
        });
    }
}
