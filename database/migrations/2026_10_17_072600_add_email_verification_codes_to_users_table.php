<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_code_hash', 64)->nullable();
            $table->uuid('email_verification_challenge_id')->nullable();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->timestamp('email_verification_requested_at')->nullable();
            $table->timestamp('email_verification_sent_at')->nullable();
            $table->timestamp('email_verification_failed_at')->nullable();
            $table->unsignedTinyInteger('email_verification_attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_challenge_id',
                'email_verification_expires_at',
                'email_verification_requested_at',
                'email_verification_sent_at',
                'email_verification_failed_at',
                'email_verification_attempts',
            ]);
        });
    }
};
