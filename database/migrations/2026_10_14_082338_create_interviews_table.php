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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('application_form_id')->constrained('application_forms')->cascadeOnDelete();
            $table->string('mode', 20);
            $table->string('platform', 30)->nullable();
            $table->string('meeting_link', 2048)->nullable();
            $table->string('venue_name')->nullable();
            $table->text('address')->nullable();
            $table->text('what_to_bring')->nullable();
            $table->text('directions')->nullable();
            $table->text('instructions')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 50)->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone', 64);
            $table->string('status', 20)->default('scheduled');
            $table->string('candidate_response', 20)->default('pending');
            $table->dateTime('responded_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['organization_id', 'status', 'start_at'], 'interviews_org_status_start_idx');
            $table->index(['application_form_id', 'start_at'], 'interviews_application_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
