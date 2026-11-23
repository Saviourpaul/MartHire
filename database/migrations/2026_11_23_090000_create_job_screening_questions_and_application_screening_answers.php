<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_screening_questions')) {
            Schema::create('job_screening_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_id')->constrained('job_posts')->cascadeOnDelete();
                $table->char('question_key', 36)->unique('job_screening_questions_key_unique');
                $table->string('type', 24);
                $table->string('prompt', 500);
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->index(['job_id', 'position'], 'job_screening_questions_job_position_idx');
            });
        }

        if (! Schema::hasTable('application_screening_answers')) {
            Schema::create('application_screening_answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('application_form_id')->constrained('application_forms')->cascadeOnDelete();
                $table->char('question_key', 36);
                $table->string('type', 24);
                $table->string('prompt', 500);
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('position')->default(0);
                $table->json('answer')->nullable();
                $table->timestamps();

                $table->unique(['application_form_id', 'question_key'], 'application_screening_answers_application_question_unique');
                $table->index(['application_form_id', 'position'], 'application_screening_answers_application_position_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_screening_answers');
        Schema::dropIfExists('job_screening_questions');
    }
};
