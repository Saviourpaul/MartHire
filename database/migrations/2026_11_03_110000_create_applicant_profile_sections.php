<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicant_profiles')) {
            Schema::create('applicant_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('professional_headline', 160)->nullable();
                $table->text('about')->nullable();
                $table->string('resume_path')->nullable();
                $table->string('resume_original_name')->nullable();
                $table->string('resume_mime_type', 120)->nullable();
                $table->unsignedBigInteger('resume_size')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_profile_employments')) {
            Schema::create('candidate_profile_employments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
                $table->string('company', 255);
                $table->string('title', 255);
                $table->string('location', 255)->nullable();
                $table->string('employment_type', 50)->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['applicant_profile_id', 'start_date'], 'candidate_employments_profile_start_date_index');
            });
        }

        if (! Schema::hasTable('candidate_profile_educations')) {
            Schema::create('candidate_profile_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
                $table->string('institution', 255);
                $table->string('qualification', 255);
                $table->string('field_of_study', 255)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['applicant_profile_id', 'end_date'], 'candidate_educations_profile_end_date_index');
            });
        }

        if (! Schema::hasTable('candidate_profile_skills')) {
            Schema::create('candidate_profile_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80);
                $table->timestamps();
                $table->unique(['applicant_profile_id', 'name']);
            });
        }

        if (! Schema::hasTable('candidate_profile_links')) {
            Schema::create('candidate_profile_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
                $table->string('platform', 32);
                $table->text('url');
                $table->timestamps();
                $table->unique(['applicant_profile_id', 'platform']);
            });
        }

        if (! Schema::hasTable('candidate_self_identifications')) {
            Schema::create('candidate_self_identifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_profile_id')->unique()->constrained()->cascadeOnDelete();
                $table->text('gender_identity')->nullable();
                $table->text('disability_status')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_self_identifications');
        Schema::dropIfExists('candidate_profile_links');
        Schema::dropIfExists('candidate_profile_skills');
        Schema::dropIfExists('candidate_profile_educations');
        Schema::dropIfExists('candidate_profile_employments');
        Schema::dropIfExists('applicant_profiles');
    }
};
