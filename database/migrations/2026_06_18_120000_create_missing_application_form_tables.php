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
        if (! Schema::hasTable('application_forms')) {
            Schema::create('application_forms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('job_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('reference')->unique();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamp('submitted_at')->useCurrent();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('email');
                $table->string('phone');
                $table->string('nationality')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->nullable();
                $table->string('marital_status')->nullable();
                $table->string('state_of_origin')->nullable();
                $table->string('local_government_area')->nullable();
                $table->string('address')->nullable();
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('profile_image_path')->nullable();

                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['job_id', 'user_id']);
                $table->index(['job_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index('submitted_at');
            });
        }

        if (! Schema::hasTable('application_documents')) {
            Schema::create('application_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_form_id')->constrained('application_forms')->cascadeOnDelete();
                $table->enum('document_type', ['nin', 'bvn', 'education']);
                $table->string('document_name');
                $table->string('document_number')->nullable();
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['application_form_id', 'document_type']);
                $table->index(['status', 'document_type']);
            });
        }

        if (! Schema::hasTable('application_status_histories')) {
            Schema::create('application_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_form_id')->constrained('application_forms')->cascadeOnDelete();
                $table->enum('from_status', ['pending', 'approved', 'rejected'])->nullable();
                $table->enum('to_status', ['pending', 'approved', 'rejected']);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['application_form_id', 'created_at'], 'app_status_hist_app_created_idx');
            });
        }

        if (
            Schema::hasTable('application_status_histories')
            && ! Schema::hasIndex('application_status_histories', 'app_status_hist_app_created_idx')
        ) {
            Schema::table('application_status_histories', function (Blueprint $table) {
                $table->index(['application_form_id', 'created_at'], 'app_status_hist_app_created_idx');
            });
        }

        if (! Schema::hasTable('application_document_status_histories')) {
            Schema::create('application_document_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_document_id');
                $table->enum('from_status', ['pending', 'approved', 'rejected'])->nullable();
                $table->enum('to_status', ['pending', 'approved', 'rejected']);
                $table->foreignId('changed_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('application_document_id', 'app_doc_status_hist_doc_fk')
                    ->references('id')
                    ->on('application_documents')
                    ->cascadeOnDelete();
                $table->foreign('changed_by', 'app_doc_status_hist_changed_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->index(['application_document_id', 'created_at'], 'app_doc_status_hist_doc_created_idx');
            });
        }
        if (
            Schema::hasTable('application_document_status_histories')
            && ! $this->hasForeignKey('application_document_status_histories', 'app_doc_status_hist_doc_fk')
        ) {
            Schema::table('application_document_status_histories', function (Blueprint $table) {
                $table->foreign('application_document_id', 'app_doc_status_hist_doc_fk')
                    ->references('id')
                    ->on('application_documents')
                    ->cascadeOnDelete();
            });
        }

        if (
            Schema::hasTable('application_document_status_histories')
            && ! $this->hasForeignKey('application_document_status_histories', 'app_doc_status_hist_changed_by_fk')
        ) {
            Schema::table('application_document_status_histories', function (Blueprint $table) {
                $table->foreign('changed_by', 'app_doc_status_hist_changed_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
        if (
            Schema::hasTable('application_document_status_histories')
            && ! Schema::hasIndex('application_document_status_histories', 'app_doc_status_hist_doc_created_idx')
        ) {
            Schema::table('application_document_status_histories', function (Blueprint $table) {
                $table->index(['application_document_id', 'created_at'], 'app_doc_status_hist_doc_created_idx');
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreignKey): bool => ($foreignKey['name'] ?? null) === $name);
    }
};
