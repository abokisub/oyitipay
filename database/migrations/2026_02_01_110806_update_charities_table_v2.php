<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCharitiesTableV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charities', function (Blueprint $table) {
            if (!Schema::hasColumn('charities', 'user_id')) {
                $table->integer('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('charities', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (!Schema::hasColumn('charities', 'logo')) {
                $table->string('logo')->nullable()->after('description');
            }

            // Link to existing user table
            $table->foreign('user_id')->references('id')->on('user')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'description', 'logo']);
        });
    }
}
