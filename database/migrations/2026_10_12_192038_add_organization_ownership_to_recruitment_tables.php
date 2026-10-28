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
        foreach (['job_posts', 'application_forms', 'application_documents', 'application_status_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('organization_id')->nullable()->index()->constrained()->restrictOnDelete();
            });
        }

        Schema::table('job_posts', function (Blueprint $table): void {
            $table->index(['organization_id', 'status', 'created_at'], 'job_posts_org_status_created_idx');
        });

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->index(['organization_id', 'status', 'submitted_at'], 'app_forms_org_status_submitted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table): void {
            $table->dropIndex('app_forms_org_status_submitted_idx');
        });
        Schema::table('job_posts', function (Blueprint $table): void {
            $table->dropIndex('job_posts_org_status_created_idx');
        });

        foreach (['application_status_histories', 'application_documents', 'application_forms', 'job_posts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
