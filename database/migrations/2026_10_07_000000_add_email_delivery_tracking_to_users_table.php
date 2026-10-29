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
            $table->timestamp('welcome_email_queued_at')->nullable()->after('email_verified_at');
            $table->timestamp('welcome_email_sent_at')->nullable()->after('welcome_email_queued_at');
        });

        // Existing accounts without a verification timestamp must not retain
        // active status merely because they were created before this gate.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->where('status', 'active')
            ->update([
                'status' => 'pending',
                'approved_at' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'welcome_email_queued_at',
                'welcome_email_sent_at',
            ]);
        });
    }
};
