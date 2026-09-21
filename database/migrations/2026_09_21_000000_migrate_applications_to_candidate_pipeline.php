<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_forms', function (Blueprint $table): void {
            $table->string('pipeline_stage')->default('submitted')->after('status');
        });

        DB::table('application_forms')->update([
            'pipeline_stage' => DB::raw("CASE status WHEN 'approved' THEN 'selected' WHEN 'rejected' THEN 'rejected' ELSE 'submitted' END"),
        ]);

        Schema::table('application_status_histories', function (Blueprint $table): void {
            $table->string('from_pipeline_stage')->nullable()->after('from_status');
            $table->string('to_pipeline_stage')->nullable()->after('to_status');
        });

        DB::table('application_status_histories')->update([
            'from_pipeline_stage' => DB::raw("CASE from_status WHEN 'approved' THEN 'selected' WHEN 'rejected' THEN 'rejected' WHEN 'pending' THEN 'submitted' ELSE NULL END"),
            'to_pipeline_stage' => DB::raw("CASE to_status WHEN 'approved' THEN 'selected' WHEN 'rejected' THEN 'rejected' ELSE 'submitted' END"),
        ]);

        foreach ([
            'application_forms_job_id_status_index',
            'application_forms_user_id_status_index',
            'app_forms_status_submitted_idx',
        ] as $index) {
            if (Schema::hasIndex('application_forms', $index)) {
                Schema::table('application_forms', function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index);
                });
            }
        }

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->dropColumn('status');
            $table->renameColumn('pipeline_stage', 'status');
            $table->index(['job_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('application_status_histories', function (Blueprint $table): void {
            $table->dropColumn(['from_status', 'to_status']);
            $table->renameColumn('from_pipeline_stage', 'from_status');
            $table->renameColumn('to_pipeline_stage', 'to_status');
        });

        // Retain document-review columns and history as read-only legacy data. They are
        // deliberately no longer exposed by models, routes, services, or views, which
        // makes the candidate pipeline the sole active progression mechanism without
        // destroying historical records during deployment.
    }

    public function down(): void
    {
        // This forward-only migration preserves the mapped candidate pipeline and audit trail.
    }
};
