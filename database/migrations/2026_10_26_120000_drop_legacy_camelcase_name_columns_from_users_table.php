<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An earlier, camelCase copy of 2026_06_11_000000_add_name_fields_to_users_table
 * ran once under its original filename (2026_10_24_000001_...) before that file
 * was renamed/fixed to snake_case. Both ran, leaving firstName/lastName as dead
 * columns alongside the real first_name/last_name. Drop the dead ones here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'firstName')) {
                $table->dropColumn('firstName');
            }
            if (Schema::hasColumn('users', 'lastName')) {
                $table->dropColumn('lastName');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstName', 255)->nullable();
            $table->string('lastName', 255)->nullable();
        });
    }
};
