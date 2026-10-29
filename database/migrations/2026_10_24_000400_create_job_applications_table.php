<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            // queued|submitting|submitted|failed_retryable|failed_credentials|failed_job_closed|failed_permanent|needs_review
            $table->string('status')->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('error_code')->nullable();
            $table->text('payload')->nullable();            // encrypted array: answers + file refs, purged on success
            $table->string('schema_hash')->nullable();
            $table->string('workable_candidate_id')->nullable();
            $table->unsignedInteger('http_status')->nullable();
            $table->text('response_excerpt')->nullable();   // truncated, PII-free
            $table->string('consent_version')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['job_posting_id', 'email']); // one application per candidate per posting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
