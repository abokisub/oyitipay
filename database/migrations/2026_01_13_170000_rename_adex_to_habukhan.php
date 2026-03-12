<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAdexToHabukhan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Rename Tables
        if (Schema::hasTable('adex_key')) {
            Schema::rename('adex_key', 'habukhan_key');
        }

        if (Schema::hasTable('adex_api')) {
            Schema::rename('adex_api', 'habukhan_api');
        }

        // 2. Rename Columns in 'user' table
        if (Schema::hasTable('user')) {
            Schema::table('user', function (Blueprint $table) {
                if (Schema::hasColumn('user', 'adex_key')) {
                    $table->renameColumn('adex_key', 'habukhan_key');
                }
            });
        }

        // 3. Rename Columns in 'message' table (if exists)
        if (Schema::hasTable('message')) {
            Schema::table('message', function (Blueprint $table) {
                if (Schema::hasColumn('message', 'adex_date')) {
                    $table->renameColumn('adex_date', 'habukhan_date');
                }
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
        // Reverse Tables
        if (Schema::hasTable('habukhan_key')) {
            Schema::rename('habukhan_key', 'adex_key');
        }

        if (Schema::hasTable('habukhan_api')) {
            Schema::rename('habukhan_api', 'adex_api');
        }

        // Reverse Columns in 'user' table
        if (Schema::hasTable('user')) {
            Schema::table('user', function (Blueprint $table) {
                if (Schema::hasColumn('user', 'habukhan_key')) {
                    $table->renameColumn('habukhan_key', 'adex_key');
                }
            });
        }

        // Reverse Columns in 'message' table
        if (Schema::hasTable('message')) {
            Schema::table('message', function (Blueprint $table) {
                if (Schema::hasColumn('message', 'habukhan_date')) {
                    $table->renameColumn('habukhan_date', 'adex_date');
                }
            });
        }
    }
}
