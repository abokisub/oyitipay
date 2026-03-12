<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug column if it doesn't exist
        if (!Schema::hasColumn('unified_banks', 'slug')) {
            Schema::table('unified_banks', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
                $table->index('slug');
            });

            // Generate slugs for existing banks
            $banks = DB::table('unified_banks')->get();
            
            foreach ($banks as $bank) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $bank->name), '-'));
                
                // Handle duplicate slugs by appending the code
                $existingSlug = DB::table('unified_banks')
                    ->where('slug', $slug)
                    ->where('id', '!=', $bank->id)
                    ->first();
                
                if ($existingSlug) {
                    $slug = $slug . '-' . $bank->code;
                }
                
                DB::table('unified_banks')
                    ->where('id', $bank->id)
                    ->update(['slug' => $slug]);
            }

            // Make slug unique after populating
            Schema::table('unified_banks', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('unified_banks', 'slug')) {
            Schema::table('unified_banks', function (Blueprint $table) {
                $table->dropIndex(['slug']);
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
