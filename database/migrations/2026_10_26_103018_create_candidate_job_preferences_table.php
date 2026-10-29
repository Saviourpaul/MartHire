<?php

use App\Enums\JobAlertFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_job_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('job_alert_frequency', 20)->default(JobAlertFrequency::Weekly->value);
            $table->boolean('profile_visible_to_employers')->default(true);
            $table->json('preferred_roles')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->string('preferred_job_type', 50)->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_job_preferences');
    }
};
