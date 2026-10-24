<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('shortcode');
            $table->string('external_id');
            $table->string('title');
            $table->string('full_title')->nullable();
            $table->string('state');
            $table->string('department')->nullable();
            $table->string('workplace_type')->nullable();
            $table->string('url')->nullable();
            $table->string('application_url')->nullable();
            $table->string('shortlink')->nullable();
            $table->json('location')->nullable();
            $table->json('locations')->nullable();
            $table->json('salary')->nullable();
            $table->json('keywords')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('content_hash')->nullable();
            $table->timestamp('external_updated_at')->nullable();
            $table->string('status')->default('open'); // open|closed
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('apply_supported')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'shortcode']);
            $table->index(['status', 'external_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
