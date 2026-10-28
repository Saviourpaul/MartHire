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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('organization_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('recruiter');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'status', 'role'], 'org_members_status_role_idx');
        });

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->restrictOnDelete();
            $table->string('email');
            $table->string('role', 30);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'email', 'status'], 'team_invitations_email_status_idx');
        });

        Schema::create('job_post_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('job_id')->constrained('job_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['job_id', 'user_id']);
            $table->index(['organization_id', 'user_id'], 'job_post_members_org_user_idx');
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('application_form_id')->nullable()->constrained('application_forms')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organization_id', 'created_at'], 'activity_logs_org_created_idx');
            $table->index(['application_form_id', 'created_at'], 'activity_logs_application_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('job_post_members');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
