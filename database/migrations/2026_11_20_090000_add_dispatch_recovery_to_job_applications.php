<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->uuid('dispatch_token')->nullable();
            $table->timestamp('dispatch_attempted_at')->nullable();
            $table->unsignedSmallInteger('dispatch_attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('processing_lease_until')->nullable();
            $table->index(
                ['status', 'next_attempt_at', 'dispatch_attempted_at'],
                'job_applications_dispatch_scan_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropIndex('job_applications_dispatch_scan_idx');
            $table->dropColumn([
                'dispatch_token',
                'dispatch_attempted_at',
                'dispatch_attempts',
                'next_attempt_at',
                'processing_lease_until',
            ]);
        });
    }
};
