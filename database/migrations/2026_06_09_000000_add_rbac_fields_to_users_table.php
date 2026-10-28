<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('applicant')->after('password')->index();
            $table->string('status', 20)->default('pending')->after('role')->index();
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('approved_at');
            $table->timestamp('last_login_at')->nullable()->after('suspended_at');
            $table->softDeletes()->after('last_login_at');
        });

        DB::table('users')->update([
            'role' => 'applicant',
            'status' => 'active',
            'approved_at' => now(),
            'suspended_at' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'last_login_at',
                'suspended_at',
                'approved_at',
                'status',
                'role',
            ]);
        });
    }
};
