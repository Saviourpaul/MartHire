<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite rebuilds these parent tables and may cascade-delete recruitment history.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (['job_posts', 'application_forms', 'application_documents', 'application_status_histories'] as $tableName) {
            if (DB::table($tableName)->whereNull('organization_id')->exists()) {
                throw new LogicException('Backfill organization ownership before requiring it.');
            }
        }

        foreach (['job_posts', 'application_forms', 'application_documents', 'application_status_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('organization_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (['job_posts', 'application_forms', 'application_documents', 'application_status_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('organization_id')->nullable()->change();
            });
        }
    }
};
